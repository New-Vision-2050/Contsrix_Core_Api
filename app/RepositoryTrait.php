<?php

namespace App;
/**
 * @author Amr
 * this trait will interact with BaseRepository class to handle logic as chain function
 *
 */

trait RepositoryTrait
{
    public function where ( array $conditions)
    {
        $this->model->where($conditions);
        return $this;

    }
}
