# 🩺 SUITABLE | Plataforma B2B Outreach, Orquestador de Campañas & Cerebro de ML

Sistema corporativo integral diseñado para **SUITABLE** ([suitable.cl](https://suitable.cl)), fabricante y distribuidor chileno de uniformes clínicos (*scrubs*) de alta gama.

---

## 👥 Credenciales de Acceso

| Rol | Correo Electrónico | Contraseña | Permisos |
|---|---|---|---|
| **Administrador** | `admin@suitable.cl` | `admin123` | Control total, configuración de llaves de IA, Brevo y WooCommerce |
| **Enviador B2B** | `ventas@suitable.cl` | `ventas123` | Pipeline de clínicas, orquestación de envíos y agendamiento de tallaje |
| **Convenios Clínicos** | `convenios@suitable.cl` | `convenios123` | Gestión de cuentas corporativas y seguimiento de cotizaciones |

*(Se incluyen botones de acceso rápido en la pantalla de inicio de sesión para facilitar las pruebas).*

---

## 🚀 Módulos y Funcionalidades

### 1. 📧 Plantillas de Correo Corporativo (Compatibles con Brevo)
* **Plantilla 1 (`email_corporativo_suitable_1.html`):** Institucional general, tecnología textil Flex, telas antifluidos, bordados y muestrario de catálogo mujer/hombre.
* **Plantilla 2 B2B (`email_corporativo_suitable_2.html`):** Enfoque corporativo de alto impacto con **imágenes autogeneradas**:
  * 🇨🇱 **Somos Fabricantes Chilenos:** Venta directa de fábrica sin intermediarios y reposición garantizada.
  * 🛡️ **6 Meses de Garantía:** Respaldo total en confección, costuras y telas.
  * 📏 **Servicio Exclusivo de Tallaje en Clínica:** Llevamos muestras y tallero (XS a 3XL) directamente a su institución.
  * 💧 **Tecnología Antifluidos & Flex 4-Way:** Demostración fotográfica de repelencia a líquidos.
  * 🏷️ **Etiquetas Brevo obligatorias integradas:** `{{ unsubscribe }}`, `{{ mirror }}`, `{{ contact.NOMBRE }}`, `{{ contact.EMPRESA }}`.

### 2. 🤖 Copiloto de Inteligencia Artificial Multi-Proveedor
* Conexión con los 4 proveedores líderes:
  * ⚡ **Groq** (`llama-3.3-70b-versatile` / `mixtral-8x7b-32768`)
  * 🧠 **OpenAI** (`gpt-4o` / `gpt-4o-mini`)
  * 🎭 **Anthropic Claude** (`claude-3-5-sonnet-20241022` / `claude-3-haiku`)
  * ✨ **Google Gemini** (`gemini-1.5-flash` / `gemini-2.0-flash`)
* **Generación inteligente en 1 clic:**
  * Asuntos B2B irresistibles para directores médicos y jefes de adquisiciones.
  * Ganchos comerciales adaptados para clínicas dentales, hospitales y centros estéticos.
  * Modo de contingencia local inteligente integrado.

### 3. 📥 Importador de Listas CSV & Segmentación por Grupos
* Detección y mapeo automático de columnas: *Empresa, Contacto, Email, Teléfono/WhatsApp, Cargo, Comuna y N° de Personal*.
* Asignación inmediata a grupos o creación de nuevos segmentos durante la importación.
* Plantilla CSV de ejemplo descargable con un clic.

### 4. 🧙 Wizard Detector de WooCommerce en Panel / cPanel
* Escaneo automático del servidor MySQL (Laragon / cPanel / subdominios).
* Detección automática de tiendas en subdominios (ej. `tienda.suitable.cl` o `b2b.suitable.cl`).
* **Preview Card en tiempo real:**
  * Subdominio detectado
  * Cantidad de pedidos históricos (`X pedidos`)
  * Ventas totales acumuladas (`$X CLP`)
  * Rango de fechas y estado
* Botón **"Conectar e Importar Tienda"** en 1 clic.

### 5. 📈 Analítica de E-commerce & Métricas de Tráfico
* Selector temporal dinámico por **Día**, **Semana**, **Mes** y **Año**.
* Cards de métricas de marketing: **CTR (Click Through Rate)**, **CPA (Costo por Adquisición)**, **AOV (Ticket Promedio)**, **CVR (Conversión)** e Ingresos.
* Gráfico interactivo de evolución de ventas.
* Ranking de categorías y colores clínicos más vendidos (Azul Marino, Celeste, Verde Nilo, etc.).

### 6. 🧠 Cerebro de Machine Learning & Tendencias
* **Tendencias de Compra:** Detección de productos en aceleración de demanda y ciclo de recompra médica (114 días promedio).
* **Tendencias de Búsqueda:** Monitor diario, semanal y mensual de términos clave de salud en Chile.
* **Forecasting & Diagnóstico de IA:** Proyección algorítmica de demanda para 30, 60 y 90 días, con reporte de abastecimiento de fábrica generado por IA.

---

## 🛠️ Tecnologías Utilizadas

* **Backend:** PHP 8.3 nativo, SQLite con PDO (portátil y ligero).
* **Frontend:** Vanilla JS + CSS3 moderno (diseño médico, glassmorphism, responsive).
* **APIs:** Groq API, OpenAI API, Anthropic Claude API, Google Gemini API, Brevo API v3, WooCommerce REST API.

---

© 2026 Suitable SpA. Marca y Fabricación Nacional de Uniformes Clínicos.
