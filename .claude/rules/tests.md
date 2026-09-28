## Tests

Los tests requieren MySQL real (no SQLite) porque el schema usa 
sintaxis exclusiva de MySQL (ADDTIME, ENGINE=InnoDB).

- Configuración: `database.tests` en `phpunit.xml.dist` apunta 
  a `turnero_test` (mismo contenedor Docker).
- Comando: `docker exec turnero_app vendor/bin/phpunit`
- El host tiene PHP 8.1, el proyecto pide 8.2+. Por eso hay que 
  correr los tests dentro del contenedor.