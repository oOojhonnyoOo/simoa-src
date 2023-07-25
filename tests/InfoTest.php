<?php

// use PHPUnit\Framework\TestCase;
// use Simoa\Info;
// use Simoa\API;

// class InfoTest extends TestCase
// {
//     private readonly Info $info;

//     public function setUp(): void
//     {
//         $_SERVER['HTTP_HOST'] = 'localhost:8080';
//         $_SERVER['REQUEST_METHOD'] = 'GET';
//         $_SERVER['HTTP_COOKIE'] = '_conasems=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJjb250YSIsImF1ZCI6ImNvbmFzZW1zIiwiaWF0IjoxNjg4OTk0NDAzLCJuYmYiOjE2ODg5OTQ0MDMsImV4cCI6MTY4OTU5OTIwMywic3ViIjoiMTE1NDM5Mjc0MDgiLCJkYXRhIjp7ImlkIjoiMiIsImNwZiI6IjExNTQzOTI3NDA4IiwidXNlcm5hbWUiOiIxMTU0MzkyNzQwOCIsInJvbGVzIjpbInJvb3QiLCJjb250YVwvdXNlciJdLCJmdWxsbmFtZSI6IkpvXHUwMGUzbyBKb3NlIGRlIFNvdXNhIE5ldG8iLCJ0b2tlbklkIjoxMX19.uN0d0RVnWLGwwHRWo7mWvVOKgfiTKbEo5tBdS3gBJGc';
//         $_SERVER['REQUEST_URI'] = 'localhost:8080';

//         $this->info = new Info(1);
//     }
    
//     public function testDefaultInitWithPathFolderEnvPath()
//     {
//         $this->assertSame($this->info->path, getenv("PATH_FOLDER"));
//     }
// }
