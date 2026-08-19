Lendable JSON Serializer
=============
[![Latest Stable Version](https://poser.pugx.org/lendable/json-serializer/v/stable)](https://packagist.org/packages/lendable/json-serializer)
[![License](https://poser.pugx.org/lendable/json-serializer/license)](https://packagist.org/packages/lendable/json-serializer)

Provides an opinionated object oriented interface for handling JSON serialization and deserialization in PHP. 

## Features
* Throws exceptions on serialization and deserialization failure.
* Sane default serialization flags.

## Installation
 ```bash	
 composer require lendable/json-serializer
 ```

## Why? Opinionated?
This library aids to simplify our most common usage patterns of JSON at Lendable by providing a strict and limited API, and not a generic solution.

### Type safety
We follow the pattern of converting object graph(s) to data array(s) and then to JSON, this library fits into the `data array(s) <=> json` part of that flow. Due to this, it is restrictive in what can be serialized and deserialized as for our use case, these are error conditions. Data is deserialized always as a numeric (array root element) or an associative array (object root element). Therefore, this library may _not_ fit your use case.

### Error handling
The default `json_encode()` and `json_decode()` global functions from `ext-json` are still used, but delegated to in a safe manner. The potential error reporting from these functions can be:

* A `false` return value - except `json_decode('false')` also returns false, and isn't an error.
* Retrieved as code or message via `json_last_error()` or `json_last_error_msg()` - requires being checked every time.
* An exception via the `JSON_THROW_ON_ERROR` option in PHP 7.3 - requires opt-in to the functionality.

This library will always throw exceptions on serialization and deserialization failures, simplifying calling logic.

## API

### Serialization `Serializer::serialize(array $data): string`
Serializes a data array into a JSON string.

Throws `SerializationFailed` on failure to serialize.

### Deserialization `Serializer::deserialize(string $json): array`
Deserializes a JSON string into an `array`.

Throws `DeserializationFailed` on failure to deserialize.
Throws `InvalidDeserializedData` if the data type of the resulting deserialized data is not an `array`.

## Local development

Everything runs inside Docker - no PHP or Composer is needed on the host. The
environment is defined in `local/` (`Dockerfile`, `docker-compose.yaml`,
`.env.dist`) and driven from the `Makefile`.

### Prerequisites

- Docker with Compose v2

### Getting started

```shell
make up      # builds the image (on first run) and starts the container
make shell   # opens a shell inside the container, in /app
make down    # stops and removes the container
```

From the shell:

```shell
composer install
composer ci
```

### Common targets

| Target | Description |
| --- | --- |
| `make build` | Build the container image |
| `make up` / `make down` | Start / tear down the environment |
| `make start` / `make stop` / `make restart` | Control the running container |
| `make ps` / `make logs` | Inspect the environment |
| `make shell` | Shell into the container as the `app` user |
| `make clean` | Tear down and remove the locally built image |

### Xdebug

Xdebug is installed but disabled by default. Copy `local/.env.dist` to
`local/.env`, set `XDEBUG_ENABLED=1` (optionally with `XDEBUG_HOST`,
`XDEBUG_PORT` and `XDEBUG_IDE_KEY`), then run `make restart`.
