
dc-ps:
	docker compose ps

dc-up:
	docker compose up -d

dc-down:
	docker compose down

dc-start:
	docker compose start

dc-stop:
	docker compose stop

dc-restart:
	docker compose restart

back-bash:
	docker exec -it corpo-ts-backend bash

back-migration-diff:
	docker exec corpo-ts-backend bash -c "composer app:migrationDiff"

back-migration-up:
	docker exec corpo-ts-backend bash -c "composer app:migrationUp"

back-php-stan:
	docker exec corpo-ts-backend bash -c "composer app:phpStan"

back-cs-fix:
	docker exec corpo-ts-backend bash -c "composer app:csFix"

# example: make back-exec composer i
back-exec:
	docker exec -it corpo-ts-backend $(filter-out $@,$(MAKECMDGOALS))

# заглушка, чтобы make не ругался на неизвестные цели (все аргументы после back-exec воспринимаются как цели).
%:
	@:
