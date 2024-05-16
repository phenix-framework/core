<?php

declare(strict_types=1);

use Amp\Http\Server\Driver\Client;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use League\Uri\Http;
use Phenix\Constants\HttpMethod;
use Phenix\Http\Requests\FormRequest;
use Phenix\Util\URL;

it('gets route attributes from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts/7/comments/22'));
    $request = new Request($client, HttpMethod::GET->value, $uri);

    $args = ['post' => '7', 'comment' => '22'];
    $request->setAttribute(Router::class, $args);

    $formRequest = FormRequest::fromRequest($request);

    expect($formRequest->route('post'))->toBe('7');
    expect($formRequest->route('comment'))->toBe('22');
    expect($formRequest->route()->integer('post'))->toBe(7);
    expect($formRequest->route()->has('post'))->toBeTrue();
    expect($formRequest->route()->has('user'))->toBeFalse();
    expect($formRequest->route()->toArray())->toBe($args);
});

it('gets query parameters from server request', function () {
    $client = $this->createMock(Client::class);
    $uri = Http::new(URL::build('posts?page=1&per_page=15&status[]=active&status[]=inactive&object[one]=1&object[two]=2'));
    $request = new Request($client, HttpMethod::GET->value, $uri);

    $formRequest = FormRequest::fromRequest($request);

    expect($formRequest->query('page'))->toBe('1');
    expect($formRequest->query('per_page'))->toBe('15');
    expect($formRequest->query('status'))->toBe(['active', 'inactive']);
    expect($formRequest->query('object'))->toBe(['one' => '1', 'two' => '2']);
});

it('can decode JSON body', function () {
    $client = $this->createMock(Client::class);

    $body = ['title' => 'Article title','content' => 'Article content'];
    $uri = Http::new(URL::build('posts'));

    $request = new Request($client, HttpMethod::POST->value, $uri);
    $request->setHeader('content-type', 'application/json');
    $request->setBody(json_encode($body));

    $formRequest = FormRequest::fromRequest($request);

    expect($formRequest->body('title'))->toBe($body['title']);
    expect($formRequest->body('content'))->toBe($body['content']);
    expect($formRequest->body()->toArray())->toBe($body);
});
