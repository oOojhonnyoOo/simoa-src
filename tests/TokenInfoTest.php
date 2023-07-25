<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Simoa\TokenInfo;

final class TokenInfoTest extends TestCase
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

  // public function testFilterWithAllowRoles()
  // {
  //   $data = (object) ['field1' => 'value1', 'field2' => 'value2', 'roles' => ['root']];
  //   $map = ['field1' => 'filter1', 'field2' => 'filter2'];

  //   $result = (new TokenInfo())->filter($data, $map, ['root']);

  //   $expectedResult = ['filtered data based on filter1', 'filtered data based on filter2', '*'];

  //   $this->assertEquals($expectedResult, $result);
  // }

  // public function testFilterWithoutAllowRoles()
  // {
  //   $data = (object) ['field1' => 'value1', 'field2' => 'value2', 'roles' => ['guest']];
  //   $map = ['field1' => 'filter1', 'field2' => 'filter2'];

  //   $result = (new TokenInfo())->filter($data, $map);

  //   $expectedResult = ['filtered data based on filter1', 'filtered data based on filter2'];

  //   $this->assertEquals($expectedResult, $result);
  // }
}