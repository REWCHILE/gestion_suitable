#!/usr/bin/env bash
# ==============================================================================
# SUITABLE B2B - SCRIPT DE INSTALACIÓN Y CAMUFLAJE PARA SERVIDOR VPS
# Configura Postfix + OpenDKIM (2048-bit) + Sanitización de Cabeceras (Header Anonymizer)
# Compatible con: Ubuntu 20.04 / 22.04 / 24.04 y Debian 11 / 12
# ==============================================================================

set -e

DOMAIN="suitable.cl"
HOSTNAME="mail.suitable.cl"
SELECTOR="default"
PUBLIC_IP=$(curl -s -4 icanhazip.com || hostname -I | awk '{print $1}')

echo "=========================================================="
echo "  🚀 INICIANDO CONFIGURACIÓN DE POSTFIX B2B PARA $DOMAIN"
echo "  IP Pública detectada: $PUBLIC_IP"
echo "  Hostname de Correo:   $HOSTNAME"
echo "=========================================================="

if [ "$EUID" -ne 0 ]; then
  echo "❌ Por favor ejecute este script como root (sudo bash setup_vps_postfix.sh)"
  exit 1
fi

# 1. Configurar Hostname
echo "[1/7] Configurando Hostname del servidor..."
hostnamectl set-hostname $HOSTNAME
echo "127.0.0.1 localhost $HOSTNAME" >> /etc/hosts

# 2. Actualizar e Instalar Paquetes
echo "[2/7] Instalando Postfix, OpenDKIM y herramientas..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
debconf-set-selections <<< "postfix postfix/mailname string $HOSTNAME"
debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
apt-get install -y postfix postfix-pcre opendkim opendkim-tools mailutils ufw

# 3. Configuración de Postfix (/etc/postfix/main.cf)
echo "[3/7] Configurando Postfix con emulación de MUA corporativo..."
cat <<EOF > /etc/postfix/main.cf
# Suitable B2B Corporate Postfix Configuration
myhostname = $HOSTNAME
mydomain = $DOMAIN
myorigin = \$mydomain
inet_interfaces = all
inet_protocols = ipv4
mydestination = localhost.\$mydomain, localhost, \$myhostname

# Ocultar banners que delaten versiones o sistemas
smtpd_banner = \$myhostname ESMTP Suitable Corporate
biff = no
append_dot_mydomain = no
readme_directory = no

# Límites de envío razonables para evitar penalizaciones
default_destination_concurrency_limit = 5
smtp_destination_rate_delay = 2s
smtp_extra_recipient_limit = 10

# Sanitización de Cabeceras (Header Checks Anonymizer)
header_checks = pcre:/etc/postfix/header_checks

# Integración con OpenDKIM Milter
milter_default_action = accept
milter_protocol = 6
smtpd_milters = inet:127.0.0.1:8891
non_smtpd_milters = inet:127.0.0.1:8891
EOF

# 4. Header Checks (Eliminar huellas de scripts de envío custom)
echo "[4/7] Configurando reglas de camuflaje de cabeceras..."
cat <<'EOF' > /etc/postfix/header_checks
# Eliminar rastros de scripts de PHP o frameworks custom
/^X-PHP-Originating-Script:/ IGNORE
/^X-Mailer:/ IGNORE
/^User-Agent:/ IGNORE
/^X-Originating-IP:/ IGNORE
/^X-Report-Abuse:/ IGNORE
/^Received:.*127\.0\.0\.1/ IGNORE
/^Received:.*\[::1\]/ IGNORE
EOF
postmap /etc/postfix/header_checks

# 5. Configurar OpenDKIM (Firma Criptográfica 2048-bit)
echo "[5/7] Generando llaves criptográficas OpenDKIM de 2048 bits..."
mkdir -p /etc/opendkim/keys/$DOMAIN

cat <<EOF > /etc/opendkim.conf
AutoRestart             Yes
AutoRestartRate         10/1h
UMask                   002
Syslog                  yes
SyslogSuccess           Yes
LogWhy                  Yes

Canonicalization        relaxed/simple
Mode                    sv
SubDomains              no
OversignHeaders         From

UserID                  opendkim:opendkim
Socket                  inet:8891@127.0.0.1

KeyTable                /etc/opendkim/KeyTable
SigningTable            /etc/opendkim/SigningTable
ExternalIgnoreList      refile:/etc/opendkim/TrustedHosts
InternalHosts           refile:/etc/opendkim/TrustedHosts
EOF

# Tablas de firma
echo "*@$DOMAIN $SELECTOR._domainkey.$DOMAIN" > /etc/opendkim/SigningTable
echo "$SELECTOR._domainkey.$DOMAIN $DOMAIN:$SELECTOR:/etc/opendkim/keys/$DOMAIN/$SELECTOR.private" > /etc/opendkim/KeyTable

cat <<EOF > /etc/opendkim/TrustedHosts
127.0.0.1
localhost
$HOSTNAME
$PUBLIC_IP
*.$DOMAIN
EOF

# Generar llave si no existe
if [ ! -f /etc/opendkim/keys/$DOMAIN/$SELECTOR.private ]; then
  opendkim-genkey -b 2048 -d $DOMAIN -D /etc/opendkim/keys/$DOMAIN -s $SELECTOR -v
  chown -R opendkim:opendkim /etc/opendkim
  chmod -R go-rwx /etc/opendkim/keys
fi

# 6. Puertos en Firewall
echo "[6/7] Habilitando puertos SMTP en el Firewall (UFW)..."
ufw allow 25/tcp comment "SMTP Standard" || true
ufw allow 587/tcp comment "SMTP Submission" || true

# 7. Reiniciar Servicios
echo "[7/7] Reiniciando Postfix y OpenDKIM..."
systemctl restart opendkim
systemctl restart postfix
systemctl enable opendkim
systemctl enable postfix

DKIM_KEY_RAW=$(cat /etc/opendkim/keys/$DOMAIN/$SELECTOR.txt | grep -v "^--" | tr -d '\n\t" ' | sed 's/.*p=/p=/')

echo ""
echo "=========================================================================="
echo "  ✅ INSTALACIÓN COMPLETADA EXITOSAMENTE EN TU VPS"
echo "=========================================================================="
echo ""
echo "📌 PASO OBLIGATORIO: Agrega estos registros DNS en tu proveedor (Cloudflare/cPanel):"
echo ""
echo "1. REGISTRO A (Para el servidor de correo):"
echo "   Tipo: A"
echo "   Nombre: mail"
echo "   Contenido: $PUBLIC_IP"
echo "   Proxy Cloudflare: DNS ONLY (Gris, NO Naranja)"
echo ""
echo "2. REGISTRO PTR / rDNS (Reverse DNS):"
echo "   Configúralo en el panel de control de tu VPS (Hetzner / DigitalOcean / OVH / Linode):"
echo "   IP: $PUBLIC_IP  -->  Debe apuntar a: $HOSTNAME"
echo ""
echo "3. REGISTRO SPF (TXT):"
echo "   Tipo: TXT"
echo "   Nombre: @"
echo "   Contenido: v=spf1 ip4:$PUBLIC_IP include:_spf.google.com ~all"
echo ""
echo "4. REGISTRO DKIM (TXT - 2048 Bits):"
echo "   Tipo: TXT"
echo "   Nombre: $SELECTOR._domainkey"
echo "   Contenido: v=DKIM1; k=rsa; $DKIM_KEY_RAW"
echo ""
echo "5. REGISTRO DMARC (TXT):"
echo "   Tipo: TXT"
echo "   Nombre: _dmarc"
echo "   Contenido: v=DMARC1; p=quarantine; rua=mailto:seguridad@$DOMAIN; pct=100; adkim=r; aspf=r;"
echo ""
echo "=========================================================================="
echo "  🧪 PRUEBA DE FUEGO:"
echo "  Envía un correo de prueba a una dirección generada en https://www.mail-tester.com"
echo "  Ejecuta en este VPS: echo 'Prueba B2B' | mail -s 'Prueba Suitable' test-xxxx@mail-tester.com"
echo "=========================================================================="
