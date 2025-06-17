# CHATBOT BACKEND

Aplicación backend para interactuar con la OPENAI.

## Levantar la aplicación

1. Configurar en .env las variables de entorno OPENAI_API_KEY y OPENAI_MODEL

   ```
   OPENAI_API_KEY="openkeyapi"
   OPENAI_MODEL="modelousado"
   ```
2. Configurar la base de datos
3. Ejecutar las migraciones y seeders

   `php artisan migrate --seed`
4. Levantar el servidor en local

   `php artisan serve`
5. Obtener un token validation haciendo una petición, puede usar curl, postman, o isomnia. Este token de validación servira para la aplicación fronted.

   ```
   curl -L 'localhost:8000/api/login' \
   -H 'Accept: application/json' \
   -H 'Content-Type: application/json' \
   --data-raw '{"email": "admin@admin.com", "password":"admin"}'
   ```
