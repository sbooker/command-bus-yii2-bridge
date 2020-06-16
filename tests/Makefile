all: up install test down cleanmake

up:
	docker-compose -p cb -f ../vendor/sbooker/command-bus/tests/Infrastructure/Persistence/build/docker-compose.yaml -f ./build/docker-compose.yaml up -d

install:
	docker-compose -p cb -f ../vendor/sbooker/command-bus/tests/Infrastructure/Persistence/build/docker-compose.yaml -f ./build/docker-compose.yaml exec app composer install

test:
	docker-compose -p cb -f ../vendor/sbooker/command-bus/tests/Infrastructure/Persistence/build/docker-compose.yaml -f ./build/docker-compose.yaml exec app ./vendor/bin/phpunit --configuration .

down:
	docker-compose -p cb -f ./build/docker-compose.yaml -f ../vendor/sbooker/command-bus/tests/Infrastructure/Persistence/build/docker-compose.yaml down

clean:
	docker rmi cb_app
