## Задействуй агента backend-architect, который должен:
- прочитать в docs/infra-vars.md вариант 1 инфраструктуры для понимания
- запомнить, что корень backend находится в папке app, composer.json находится в папке app, минимальный набор фреймворка symfony 8 уже установлен
- запомнить, что backend находится в контейнере docker с именем corpo-ts-backend, для исполнения команд в контейнере нужно использовать на уровне папки app команду `make back-exec <command>`   
- установить следующие пакеты composer для разработки:
  * doctrine для работы с БД
  * миграции doctrine
  * nelmio api doc
  * vich uploader
  * пакеты symfony для работы с почтой, twig, asset, сериализацией, аутентификации и авторизации, валидацией
- установить следующие пакеты composer для разработки (dev среда):
  * php csfixer
  * phpstan
  * phpunit
  * symplify config transformer для symfony
  * deptrac deptrac
