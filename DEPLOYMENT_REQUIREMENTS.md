# Requisitos para despliegue de B2B Conector

Para que la generación de PDFs con imágenes funcione correctamente en producción, asegúrate de instalar las siguientes dependencias en el servidor:

## Dependencias del sistema

- PHP (>=7.1)
- Extensión PHP GD (para imágenes en PDF)
- Extensión PHP MBString
- Extensión PHP DOM

### Instalación en Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install php-gd php-mbstring php-xml
sudo systemctl restart apache2 # o php-fpm según tu stack
```

### Instalación en CentOS/RHEL

```bash
sudo yum install php-gd php-mbstring php-xml
sudo systemctl restart httpd
```

### Verificar que GD está activa

```bash
php -m | grep gd
```
Debe aparecer `gd` en la lista.

## Permisos
- Los archivos de imagen deben ser legibles por el usuario que ejecuta PHP.

## Docker
Si usas Docker, agrega en tu Dockerfile:
```Dockerfile
RUN apt-get update && apt-get install -y php-gd php-mbstring php-xml
```

## Notas
- Si usas una versión específica de PHP, instala el paquete correspondiente (por ejemplo, `php8.1-gd`).
- Si usas un proveedor de hosting, revisa su panel para activar la extensión GD.

---

**Sin la extensión GD, dompdf no podrá renderizar imágenes en los PDFs.**
