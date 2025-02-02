<?php

declare(strict_types=1);

use Phenix\Database\Alias;
use Phenix\Database\Constants\Operator;
use Phenix\Database\Functions;
use Phenix\Database\QueryGenerator;
use Phenix\Database\Subquery;
use Phenix\Database\Value;
use Phenix\Exceptions\QueryError;

it('generates query to select all columns of table', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users');
    expect($params)->toBeEmpty();
});

it('generates query to select all columns from table', function () {
    $query = new QueryGenerator();

    $sql = $query->from('users')
        ->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users');
    expect($params)->toBeEmpty();
});

it('generates a query using sql functions', function (string $function, string $column, string $rawFunction) {
    $query = new QueryGenerator();

    $sql = $query->table('products')
        ->select([Functions::{$function}($column)])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT {$rawFunction} FROM products");
    expect($params)->toBeEmpty();
})->with([
    ['avg', 'price', 'AVG(price)'],
    ['sum', 'price', 'SUM(price)'],
    ['min', 'price', 'MIN(price)'],
    ['max', 'price', 'MAX(price)'],
    ['count', 'id', 'COUNT(id)'],
]);

it('generates a query using sql functions with alias', function (
    string $function,
    string $column,
    string $alias,
    string $rawFunction
) {
    $query = new QueryGenerator();

    $sql = $query->table('products')
        ->select([Functions::{$function}($column)->as($alias)])
        ->get();

    [$dml, $params] = $sql;

    expect($dml)->toBe("SELECT {$rawFunction} FROM products");
    expect($params)->toBeEmpty();
})->with([
    ['avg', 'price', 'value', 'AVG(price) AS value'],
    ['sum', 'price', 'value', 'SUM(price) AS value'],
    ['min', 'price', 'value', 'MIN(price) AS value'],
    ['max', 'price', 'value', 'MAX(price) AS value'],
    ['count', 'id', 'value', 'COUNT(id) AS value'],
]);

it('selects field from subquery', function () {
    $query = new QueryGenerator();

    $date = date('Y-m-d');
    $sql = $query->select(['id', 'name', 'email'])
        ->from(function (Subquery $subquery) use ($date) {
            $subquery->from('users')
                ->whereEqual('verified_at', $date);
        })
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, name, email FROM (SELECT * FROM users WHERE verified_at = ?)";

    expect($dml)->toBe($expected);
    expect($params)->toBe([$date]);
});


it('generates query using subqueries in column selection', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'id',
            'name',
            Subquery::make()->select(['name'])
                ->from('countries')
                ->whereColumn('users.country_id', 'countries.id')
                ->as('country_name')
                ->limit(1),
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $subquery = "SELECT name FROM countries WHERE users.country_id = countries.id LIMIT 1";
    $expected = "SELECT id, name, ({$subquery}) AS country_name FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('throws exception on generate query using subqueries in column selection with limit missing', function () {
    expect(function () {
        $query = new QueryGenerator();

        $query->select([
                'id',
                'name',
                Subquery::make()->select(['name'])
                    ->from('countries')
                    ->whereColumn('users.country_id', 'countries.id')
                    ->as('country_name'),
            ])
            ->from('users')
            ->get();
    })->toThrow(QueryError::class);
});

it('generates query with column alias', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'id',
            Alias::of('name')->as('full_name'),
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, name AS full_name FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query with many column alias', function () {
    $query = new QueryGenerator();

    $sql = $query->select([
            'id' => 'model_id',
            'name' => 'full_name',
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id AS model_id, name AS full_name FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query with select-cases using comparisons', function (
    string $method,
    array $data,
    string $defaultResult,
    string $operator
) {
    [$column, $value, $result] = $data;

    $value = Value::from($value);

    $query = new QueryGenerator();

    $case = Functions::case()
        ->{$method}($column, $value, $result)
        ->defaultResult($defaultResult)
        ->as('type');

    $sql = $query->select([
            'id',
            'description',
            $case,
        ])
        ->from('products')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, description, (CASE WHEN {$column} {$operator} {$value} "
        . "THEN {$result} ELSE $defaultResult END) AS type FROM products";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['whenEqual', ['price', 100, 'expensive'], 'cheap', Operator::EQUAL->value],
    ['whenDistinct', ['price', 100, 'expensive'], 'cheap', Operator::DISTINCT->value],
    ['whenGreaterThan', ['price', 100, 'expensive'], 'cheap', Operator::GREATER_THAN->value],
    ['whenGreaterThanOrEqual', ['price', 100, 'expensive'], 'cheap', Operator::GREATER_THAN_OR_EQUAL->value],
    ['whenLessThan', ['price', 100, 'cheap'], 'expensive', Operator::LESS_THAN->value],
    ['whenLessThanOrEqual', ['price', 100, 'cheap'], 'expensive', Operator::LESS_THAN_OR_EQUAL->value],
]);

it('generates query with select-cases using logical comparisons', function (
    string $method,
    array $data,
    string $defaultResult,
    string $operator
) {
    [$column, $result] = $data;

    $query = new QueryGenerator();

    $case = Functions::case()
        ->{$method}(...$data)
        ->defaultResult($defaultResult)
        ->as('status');

    $sql = $query->select([
            'id',
            'name',
            $case,
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, name, (CASE WHEN {$column} {$operator} "
        . "THEN {$result} ELSE $defaultResult END) AS status FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
})->with([
    ['whenNull', ['created_at', 'inactive'], 'active', Operator::IS_NULL->value],
    ['whenNotNull', ['created_at', 'active'], 'inactive', Operator::IS_NOT_NULL->value],
    ['whenTrue', ['is_verified', 'active'], 'inactive', Operator::IS_TRUE->value],
    ['whenFalse', ['is_verified', 'inactive'], 'active', Operator::IS_FALSE->value],
]);

it('generates query with select-cases with multiple conditions and string values', function () {
    $date = date('Y-m-d H:i:s');

    $query = new QueryGenerator();

    $case = Functions::case()
        ->whenNull('created_at', Value::from('inactive'))
        ->whenGreaterThan('created_at', Value::from($date), Value::from('new user'))
        ->defaultResult(Value::from('old user'))
        ->as('status');

    $sql = $query->select([
            'id',
            'name',
            $case,
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, name, (CASE WHEN created_at IS NULL THEN 'inactive' "
        . "WHEN created_at > '{$date}' THEN 'new user' ELSE 'old user' END) AS status FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query with select-cases without default value', function () {
    $date = date('Y-m-d H:i:s');

    $query = new QueryGenerator();

    $case = Functions::case()
        ->whenNull('created_at', Value::from('inactive'))
        ->whenGreaterThan('created_at', Value::from($date), Value::from('new user'))
        ->as('status');

    $sql = $query->select([
            'id',
            'name',
            $case,
        ])
        ->from('users')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, name, (CASE WHEN created_at IS NULL THEN 'inactive' "
        . "WHEN created_at > '{$date}' THEN 'new user' END) AS status FROM users";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query with select-case using functions', function () {
    $query = new QueryGenerator();

    $case = Functions::case()
        ->whenGreaterThanOrEqual(Functions::avg('price'), 4, Value::from('expensive'))
        ->defaultResult(Value::from('cheap'))
        ->as('message');

    $sql = $query->select([
            'id',
            'description',
            'price',
            $case,
        ])
        ->from('products')
        ->get();

    [$dml, $params] = $sql;

    $expected = "SELECT id, description, price, (CASE WHEN AVG(price) >= 4 THEN 'expensive' ELSE 'cheap' END) "
        . "AS message FROM products";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('counts all records', function () {
    $query = new QueryGenerator();

    $sql = $query->from('products')->count();

    [$dml, $params] = $sql;

    $expected = "SELECT COUNT(*) FROM products";

    expect($dml)->toBe($expected);
    expect($params)->toBeEmpty();
});

it('generates query to check if record exists', function () {
    $query = new QueryGenerator();

    $sql = $query->from('products')
        ->whereEqual('id', 1)
        ->exists();

    [$dml, $params] = $sql;

    $expected = "SELECT EXISTS"
        . " (SELECT 1 FROM products WHERE id = ?) AS 'exists'";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
});

it('generates query to check if record does not exist', function () {
    $query = new QueryGenerator();

    $sql = $query->from('products')
        ->whereEqual('id', 1)
        ->doesntExist();

    [$dml, $params] = $sql;

    $expected = "SELECT NOT EXISTS"
        . " (SELECT 1 FROM products WHERE id = ?) AS 'exists'";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
});

it('generates query to select first row', function () {
    $query = new QueryGenerator();

    $sql = $query->from('products')
        ->whereEqual('id', 1)
        ->first();

    [$dml, $params] = $sql;

    $expected = "SELECT * FROM products WHERE id = ? LIMIT 1";

    expect($dml)->toBe($expected);
    expect($params)->toBe([1]);
});

it('generates query to select all columns of table without column selection', function () {
    $query = new QueryGenerator();

    $sql = $query->table('users')->get();

    expect($sql)->toBeArray();

    [$dml, $params] = $sql;

    expect($dml)->toBe('SELECT * FROM users');
    expect($params)->toBeEmpty();
});
