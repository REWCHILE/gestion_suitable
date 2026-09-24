<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function index(): View
    {
        $groups = ContactGroup::orderBy('name')->get();
        return view('import.index', compact('groups'));
    }

    public function process(Request $request): View
    {
        $groupId = (int)$request->input('group_id', 0);
        $newGroupName = trim($request->input('new_group_name', ''));
        $content = '';

        if ($groupId === -1 && $newGroupName) {
            $group = ContactGroup::create([
                'name' => $newGroupName,
                'description' => 'Grupo creado desde importador de contactos',
                'color' => '#1E8888'
            ]);
            $groupId = $group->id;
        }

        if ($request->hasFile('csv_file') && $request->file('csv_file')->isValid()) {
            $content = file_get_contents($request->file('csv_file')->getRealPath());
        } elseif ($request->filled('csv_text')) {
            $content = trim($request->input('csv_text'));
        }

        $inserted = 0;
        $updated = 0;
        $errors = 0;

        if ($content) {
            $isBrevoPaste = (stripos($content, 'app.brevo.com') !== false || stripos($content, 'EmailSMS') !== false || stripos($content, 'LISTA ENCUESTA') !== false);

            if ($isBrevoPaste) {
                preg_match_all('/\[([^\]]+)\]\(https:\/\/app\.brevo\.com\/contact\/index\/(\d+)\)/i', $content, $brevoMatches, PREG_SET_ORDER);
                if (empty($brevoMatches)) {
                    preg_match_all('/[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+/i', $content, $rawEmails);
                    $uniqueEmails = array_unique(array_map('strtolower', $rawEmails[0] ?? []));
                    foreach ($uniqueEmails as $em) {
                        $brevoMatches[] = [$em, $em, '0'];
                    }
                }

                $seen = [];
                foreach ($brevoMatches as $bm) {
                    $rawEm = trim($bm[1] ?? '');
                    $brevoId = trim($bm[2] ?? '');
                    if (!$rawEm || isset($seen[strtolower($rawEm)])) continue;
                    $seen[strtolower($rawEm)] = true;

                    $enriched = self::enrichContact($rawEm);
                    $email = $enriched['email'];

                    $client = Client::whereRaw('LOWER(email) = ?', [$email])->first();

                    if ($client) {
                        $client->update([
                            'empresa' => $enriched['empresa'],
                            'contacto_nombre' => $enriched['contacto'],
                            'cargo' => $enriched['cargo'],
                            'region_comuna' => $enriched['comuna'],
                        ]);
                        $updated++;
                    } else {
                        $client = Client::create([
                            'empresa' => $enriched['empresa'],
                            'contacto_nombre' => $enriched['contacto'],
                            'email' => $email,
                            'cargo' => $enriched['cargo'],
                            'region_comuna' => $enriched['comuna'],
                            'tamano_equipo' => 15,
                            'estado' => 'nuevo',
                            'notas' => $brevoId ? "Importado de Brevo ID #$brevoId" : "Importado de Brevo"
                        ]);
                        $inserted++;
                    }

                    if ($groupId > 0) {
                        $client->groups()->syncWithoutDetaching([$groupId]);
                    }
                }
            } else {
                // Delimited CSV
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                if (count($lines) >= 1) {
                    $firstLine = $lines[0];
                    $delim = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
                    $headers = str_getcsv(array_shift($lines), $delim);
                    $headers = array_map(fn($h) => strtolower(trim(str_replace(['"', "'", "\xEF\xBB\xBF"], '', $h))), $headers);

                    $colMap = ['empresa' => -1, 'contacto' => -1, 'email' => -1, 'telefono' => -1, 'cargo' => -1, 'comuna' => -1];
                    foreach ($headers as $idx => $h) {
                        if (preg_match('/(empresa|clinica|centro|institucion)/i', $h)) $colMap['empresa'] = $idx;
                        if (preg_match('/(nombre|contacto|representante)/i', $h)) $colMap['contacto'] = $idx;
                        if (preg_match('/(email|correo|mail)/i', $h)) $colMap['email'] = $idx;
                        if (preg_match('/(tel|fono|whatsapp|celular)/i', $h)) $colMap['telefono'] = $idx;
                        if (preg_match('/(cargo|puesto|rol)/i', $h)) $colMap['cargo'] = $idx;
                        if (preg_match('/(comuna|ciudad|region)/i', $h)) $colMap['comuna'] = $idx;
                    }
                    if ($colMap['email'] === -1 && isset($headers[2])) $colMap['email'] = 2;

                    foreach ($lines as $line) {
                        if (!trim($line)) continue;
                        $row = str_getcsv($line, $delim);
                        $rawEmail = trim($row[$colMap['email']] ?? '');
                        if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
                            $errors++;
                            continue;
                        }

                        $rawEmpresa = trim($row[$colMap['empresa']] ?? '');
                        $rawContacto = trim($row[$colMap['contacto']] ?? '');
                        $rawCargo = trim($row[$colMap['cargo']] ?? '');
                        $rawComuna = trim($row[$colMap['comuna']] ?? '');
                        $telefono = trim($row[$colMap['telefono']] ?? '');

                        $enriched = self::enrichContact($rawEmail, $rawContacto, $rawEmpresa, $rawCargo, $rawComuna);
                        $client = Client::whereRaw('LOWER(email) = ?', [$enriched['email']])->first();

                        if ($client) {
                            $client->update([
                                'empresa' => $enriched['empresa'],
                                'contacto_nombre' => $enriched['contacto'],
                                'telefono' => $telefono ?: $client->telefono,
                                'cargo' => $enriched['cargo'],
                                'region_comuna' => $enriched['comuna'],
                            ]);
                            $updated++;
                        } else {
                            $client = Client::create([
                                'empresa' => $enriched['empresa'],
                                'contacto_nombre' => $enriched['contacto'],
                                'email' => $enriched['email'],
                                'telefono' => $telefono,
                                'cargo' => $enriched['cargo'],
                                'region_comuna' => $enriched['comuna'],
                                'tamano_equipo' => 20,
                                'estado' => 'nuevo',
                                'notas' => 'Importado desde CSV'
                            ]);
                            $inserted++;
                        }

                        if ($groupId > 0) {
                            $client->groups()->syncWithoutDetaching([$groupId]);
                        }
                    }
                }
            }
        }

        $importResults = [
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors,
            'group_id' => $groupId
        ];

        $groups = ContactGroup::orderBy('name')->get();

        return view('import.index', compact('groups', 'importResults'));
    }

    public static function enrichContact(string $email, string $rawName = '', string $rawEmpresa = '', string $rawCargo = '', string $rawComuna = ''): array
    {
        $clean = strtolower(trim($email));
        $parts = explode('@', $clean, 2);
        $local = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        $empresa = $rawEmpresa ?: 'Institución de Salud';
        $cargo = $rawCargo ?: 'Profesional de Salud';
        $comuna = $rawComuna ?: 'Santiago, RM';
        $contacto = $rawName;

        $domainMap = [
            'ug.uchile.cl' => ['Universidad de Chile (Salud)', 'Santiago'],
            'uchile.cl' => ['Universidad de Chile', 'Santiago'],
            'uc.cl' => ['Red de Salud UC CHRISTUS', 'Santiago'],
            'mayor.cl' => ['Universidad Mayor / Red Salud', 'Huechuraba'],
            'udd.cl' => ['Universidad del Desarrollo', 'Las Condes'],
            'uandresbello.edu' => ['Universidad Andrés Bello', 'Santiago'],
            'renca.cl' => ['Corporación Municipal Salud Renca', 'Renca'],
            'obesidadyestetica.cl' => ['Clínica Obesidad y Estética', 'Las Condes'],
            'somoslabtech.com' => ['LabTech Equipamiento', 'Santiago'],
            'somoslabtech.cl' => ['LabTech Diagnóstica', 'Santiago'],
            'benefithealth.cl' => ['Benefit Health Chile', 'Providencia'],
            'nsglobal.cl' => ['NS Global Medical', 'Santiago'],
            'botanicalsoluttions.cl' => ['Botanical Solutions Chile', 'Santiago'],
            'policomp.com' => ['Policomp Salud', 'Santiago'],
            'medicina.ucsc.cl' => ['Facultad de Medicina UCSC', 'Concepción']
        ];

        foreach ($domainMap as $d => $info) {
            if ($domain === $d || str_ends_with($domain, '.' . $d)) {
                if ($empresa === 'Institución de Salud' || empty($rawEmpresa)) $empresa = $info[0];
                if ($comuna === 'Santiago, RM' || empty($rawComuna)) $comuna = $info[1];
                break;
            }
        }

        if (!$contacto) {
            if (str_starts_with($local, 'dr.') || str_starts_with($local, 'dr_')) {
                $cargo = 'Médico Cirujano / Especialista';
                $contacto = 'Dr. ' . ucwords(str_replace(['.', '_'], ' ', preg_replace('/^dr[._]/', '', $local)));
            } elseif (str_starts_with($local, 'dra.') || str_starts_with($local, 'dra_')) {
                $cargo = 'Médica Cirujana / Especialista';
                $contacto = 'Dra. ' . ucwords(str_replace(['.', '_'], ' ', preg_replace('/^dra[._]/', '', $local)));
            } elseif (str_starts_with($local, 'ps.') || str_starts_with($local, 'ps_')) {
                $cargo = 'Psicólogo/a Clínico/a';
                $contacto = 'Ps. ' . ucwords(str_replace(['.', '_'], ' ', preg_replace('/^ps[._]/', '', $local)));
            } elseif (stripos($local, 'kinesiolog') !== false) {
                $cargo = 'Kinesiólogo/a';
                $contacto = ucwords(str_replace(['.', '_'], ' ', preg_replace('/kinesiolog[ao]?/i', '', $local)));
                if ($empresa === 'Institución de Salud') $empresa = 'Centro de Kinesiología';
            } elseif (stripos($local, 'veterinari') !== false) {
                $cargo = 'Médico Veterinario / Directora';
                $contacto = ucwords(str_replace(['.', '_'], ' ', $local));
                $empresa = 'Clínica Veterinaria ' . ucwords(str_ireplace('veterinaria', '', $local));
            } else {
                $s = preg_replace('/\d+/', ' ', $local);
                $s = preg_replace('/[._-]+/', ' ', $s);
                $contacto = ucwords(strtolower(trim($s)));
                if ($empresa === 'Institución de Salud') $empresa = 'Consulta / ' . $contacto;
            }
        }

        return [
            'email' => $clean,
            'contacto' => trim($contacto ?: 'Profesional de Salud'),
            'empresa' => trim($empresa),
            'cargo' => trim($cargo),
            'comuna' => $comuna
        ];
    }
}
