<?php

use PHPUnit\Framework\TestCase;
use Simoa\TokenInfo;

class TokenInfoTest extends TestCase
{
    public function testAvaCursos()
    {
        $tokenData = (object) [
            'roles' => ['root', 'ava/admin', 'ava/some_course/coordinator-general'],
            'extraInfo' => (object)[
                'ava' => (object)[
                    'turmas' => [
                        (object)['curso' => 'Math'],
                        (object)['curso' => 'Science'],
                    ]
                ]
            ]
        ];

        $cursos = TokenInfo::avaCursos($tokenData);

        $this->assertContains('Math', $cursos);
        $this->assertContains('Science', $cursos);

        $this->assertContains('*', $cursos);

        $this->assertCount(3, $cursos);
    }
}