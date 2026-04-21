<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AcademicSpecializationFilter extends SearchModelFilter
{
       public $relations = [];

       public function name($name)
       {
           return $this->whereHas('translations',function($q) use ($name){
               $q->where('content','like','%'.$name.'%');
           });
       }
//        public function academicQualification($academic_qualification_id)
//       {
//           return $this->where('academic_qualification_id',$academic_qualification_id);
//       }
}
