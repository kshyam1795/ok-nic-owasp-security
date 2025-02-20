# Laravel OWASP Security Package

## Introduction
`ok-nic-owasp-security` is a Laravel package that implements OWASP security best practices, including:
- Secure HTTP headers
- Cross-Site Scripting (XSS) protection
- SQL Injection mitigation
- Rate limiting
- Cross-Origin Resource Sharing (CORS) protection

## Installation
Install the package using Composer:

```sh
composer require sam/ok-nic-owasp-security



##Publish Configuration
 
php artisan vendor:publish --tag=owasp-security 

This will create the configuration file at:

 
config/owasp-security.php

## Register Middleware
Add the middleware to app/Http/Kernel.php under $middleware:

protected $middleware = [
    \Sam\OkNicOwaspSecurity\Middleware\SecurityHeaders::class,
    \Sam\OkNicOwaspSecurity\Middleware\XssSanitization::class,
    \Sam\OkNicOwaspSecurity\Middleware\RateLimiting::class,
    \Sam\OkNicOwaspSecurity\Middleware\SqlInjectionProtection::class,
    \Sam\OkNicOwaspSecurity\Middleware\CorsProtection::class,
];


Middleware Explanation | Middleware	Description
SecurityHeaders	       | Adds security headers (CSP, HSTS, XSS Protection)
XssSanitization	       | Strips malicious scripts from input
RateLimiting	       | Limits excessive requests to prevent DoS attacks
SqlInjectionProtection | Blocks common SQL injection patterns
CorsProtection	       | Restricts cross-origin requests