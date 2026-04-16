
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

back-php-stan:
	docker exec corpo-ts-backend bash -c "composer phpStan"

back-cs-fix:
	docker exec corpo-ts-backend bash -c "composer csFix"

# example: make back-exec composer i
back-exec:
	docker exec -it corpo-ts-backend $(filter-out $@,$(MAKECMDGOALS))

# заглушка, чтобы make не ругался на неизвестные цели (все аргументы после back-exec воспринимаются как цели).
%:
	@:
