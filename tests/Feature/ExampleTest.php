<?php

it('redirects guests from home to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
