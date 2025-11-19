<?php
 if(!function_exists('isActive')){
     function isActive($routes, $class = 'mm-active')
     {
         if (is_array($routes)) {
             return in_array(request()->route()->getName(), $routes) ? $class : '';
         }

         return request()->routeIs($routes) ? $class : '';
     }
 }
