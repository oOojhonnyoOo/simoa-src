<?php

namespace Simoa;

class TokenInfo
{
  /**
   * filtra pelo map enviado separado por ponto
   */
  static function filter($data, $map, $allowRoles = ['root', 'ava/admin'])
  {
    $return = Helper::filterData($data, $map);
    if (array_intersect($allowRoles, $data->roles)) {
      $return[] = "*";
    }
    return $return;
  }

  static function avaCursos($tokenData)
  {
    $cursos = [];

    if (isset($tokenData->extraInfo->ava->turmas)) {
      foreach ($tokenData->extraInfo->ava->turmas as $item) {
        $cursos[] = $item->curso;
      }
    }

    if (array_intersect(['root', 'ava/admin'], $tokenData->roles)) {
      $cursos[] = "*";
    }

    foreach ($tokenData->roles as $r) {
      if (preg_match("/ava\/(.*?)\/coordenador\-geral/", $r, $match)) {
        $cursos[] = $match[1];
      }
    }

    return $cursos;
  }
}
