
# example: make back-exec composer i
back-exec:
	docker exec -it corpo-ts-backend $(filter-out $@,$(MAKECMDGOALS))

# заглушка, чтобы make не ругался на неизвестные цели (все аргументы после back-exec воспринимаются как цели).
%:
	@:
