<?php

namespace Tightenco\Overload;

use Closure;
use InvalidArgumentException;
use Tightenco\Collect\Support\Collection;

trait Overloadable
{
    public function overload($args, $signatures, $fallback = null)
    {
        $candidate = (new Collection($signatures))->map(function ($value, $key) {
            return new OverloadedMethodCandidate($value, $key, $this);
        })->first(function ($candidate) use ($args) {
            return $candidate->matches($args);
        });

        if (!$candidate instanceof OverloadedMethodCandidate) {
            if (func_num_args() == 3) {
                if ($fallback instanceof Closure) {
                    $fallback->bindTo($this);
                    return $fallback($args);
                }
                return $fallback;
            }

            $types = (new Collection($args))->map(function ($arg) {
                $type = gettype($arg);

                if ($type == 'object') {
                    $type = get_class($arg);
                }

                return $type;
            });

            $count = $types->count();
            $s = $count == 1 ? '' : 's';

            $message = '';

            if ($count) {
                $types = $types->implode(', ');
                $message = " of type$s $types";
            }

            throw new InvalidArgumentException("No overloaded method found for $count argument$s$message");
        }

        return $candidate->call($args);
    }
}
