# Laravel Google Maps Project

Este repositorio contiene una aplicación **Laravel 11** que permite buscar y resaltar polígonos (ZIP codes y “places”) en un mapa de Google Maps, usando datos GeoJSON importados a MySQL. Está preparada para desplegarse con **Docker Compose**.

---

## Requisitos

- Docker & Docker Compose  
- Git  
- Windows, macOS o Linux  

---

## Estructura de carpetas clave

```
/
├── app/
├── bootstrap/
├── config/
├── database/
│   └── migrations/
├── nginx/
│   └── conf.d/
├── public/
├── resources/
├── storage/
│   └── app/
│       └── geojson/       ← Coloca aquí tus archivos .geojson
├── .dockerignore
├── Dockerfile
├── docker-compose.yml
├── composer.json
└── README.md
```

---

## 1. Clonar y configurar el proyecto

```bash
git clone https://github.com/tu-usuario/tu-repo.git
cd tu-repo
cp .env.example .env
```

Edita `.env` con tus credenciales, por ejemplo:

```dotenv
APP_NAME=Laravel
APP_ENV=local
APP_KEY=

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

> **Nota:** Si cambias el mapeo de puertos en `docker-compose.yml` (por ejemplo a `3307:3306`), actualiza `DB_PORT` en tu `.env` también.

---

## 2. Construir y levantar con Docker

```bash
docker-compose up -d --build
```

Esto creará tres contenedores:

- **app**: PHP-FPM con tu código  
- **web**: Nginx sirviendo `public/`  
- **db**: MySQL 8.0  

---

## 3. Preparar la aplicación

```bash
# Generar la APP_KEY
docker-compose exec app php artisan key:generate

# Ejecutar migraciones
docker-compose exec app php artisan migrate
```

---

## 4. Importar datos GeoJSON

Copia tus archivos GeoJSON (p.e. `zcta.geojson` y `places.geojson`) en:

```
storage/app/geojson/
```

Luego importa cada uno con el comando Artisan:

```bash
# Importar ZIP codes
docker-compose exec app php artisan import:locations zcta.geojson --type=zipcode

# Importar “places”
docker-compose exec app php artisan import:locations places.geojson --type=place
```

Opcionalmente ajusta el tamaño de batch:

```bash
docker-compose exec app php artisan import:locations zcta.geojson --type=zipcode --batch=20
```

---

## 5. Acceder a la aplicación

Abre tu navegador en:

```
http://localhost:8080
```

---

## 6. Comandos útiles

- **Ver logs de Laravel**  
  ```bash
  docker-compose logs -f app
  ```
- **Ejecutar tinker**  
  ```bash
  docker-compose exec app php artisan tinker
  ```
- **Ejecutar pruebas**  
  ```bash
  docker-compose exec app php artisan test
  ```
- **Reconstruir solo la app**  
  ```bash
  docker-compose build app
  ```

---

## 7. Limpieza

- Parar y eliminar contenedores & volúmenes:

  ```bash
  docker-compose down -v
  ```

- Borrar la imagen:

  ```bash
  docker image rm laravel-app
  ```

¡Listo! Con estos pasos tu aplicación Laravel quedará completamente dockerizada y lista para desplegar con un solo comando.
