<?php

namespace App;
/**
 * @author Amr
 * this trait will interact with BaseRepository class to handle logic as chain function
 *
 */

trait RepositoryTrait
{
    public function whereInIds ($auditIds)
    {
        $this->model->whereIn("id",$auditIds);
        return $this;

    }
}
