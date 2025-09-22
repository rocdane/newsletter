<?php

namespace App;
use Illuminate\Support\Facades\Log;

trait ObjectLogger {

    public function logCreation(?string $message = ''): void
    {
        $objectId = spl_object_id($this);
        $className = get_class($this);
        Log::info($message."\t[{$objectId}] +++ CREATE {$className}");
    }
    
    public function logDestruction(): void
    {
        $objectId = spl_object_id($this);
        $className = get_class($this);
        Log::warn("[{$objectId}] --- DESTROY {$className}");
    }
    
    public function logMethodCall(string $methodName): void
    {
        $objectId = spl_object_id($this);
        Log::info("[{$objectId}] ==> CALL {$methodName}");
    }
    
    public function getObjectIdentifier(): string
    {
        return get_class($this) . '#' . spl_object_id($this);
    }
}
