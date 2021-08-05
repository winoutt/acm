<?php

$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('moderate', 'ModerateController@moderate');
});
