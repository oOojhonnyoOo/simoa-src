<?php declare(strict_types=1);

// use PHPUnit\Framework\TestCase;
// use Simoa\Token;
// use Simoa\Config;
// use Simoa\Helper;

// final Class TokenTest extends TestCase
// {
//     private readonly Token $token;

//     public function setUp(): void
//     {
//         $_SERVER['HTTP_HOST'] = 'localhost:8080';
//         $_SERVER['REQUEST_METHOD'] = 'GET';
//         $_SERVER['HTTP_COOKIE'] = '_conasems=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb250YSIsImF1ZCI6ImNvbmFzZW1zIiwiaWF0IjoxNjg4OTk0NDAzLCJuYmYiOjE2ODg5OTQ0MDMsImV4cCI6MTY4OTU5OTIwMywic3ViIjoiMTE1NDM5Mjc0MDgiLCJkYXRhIjp7ImlkIjoiMiIsImNwZiI6IjExNTQzOTI3NDA4IiwidXNlcm5hbWUiOiIxMTU0MzkyNzQwOCIsInJvbGVzIjpbInJvb3QiLCJjb250YVwvdXNlciJdLCJmdWxsbmFtZSI6IkpvXHUwMGUzbyBKb3NlIGRlIFNvdXNhIE5ldG8iLCJ0b2tlbklkIjoxMX19.uN0d0RVnWLGwwHRWo7mWvVOKgfiTKbEo5tBdS3gBJGc';
//         $_SERVER['REQUEST_URI'] = 'localhost:8080';
//         $_SERVER['TOKEN'] = 'test';

//         $this->token = new Token(new Config);
//     }

//     public function testDataWithInvalidToken()
//     {
//         exit(var_dump(Helper::getallheaders()));
//         $this->token->data->token = $_SERVER['TOKEN'];

//         $this->assertFalse($this->token->data(Helper::getallheaders()));
//     }

    // public function testAllowedWithInvalidToken()
    // {
    //     $this->token->data('test');

    //     $this->assertFalse($this->token->allowed('root'));
    // }
// }
