<?php

namespace App\Support;

class ActionStyle
{
    public static function create(object $action): object
    {
        return $action
            ->icon('heroicon-o-plus-circle')
            ->color('success');
    }

    public static function view(object $action): object
    {
        return $action
            ->icon('heroicon-o-eye')
            ->color('gray');
    }

    public static function edit(object $action): object
    {
        return $action
            ->icon('heroicon-o-pencil-square')
            ->color('warning');
    }

    public static function delete(object $action): object
    {
        return $action
            ->icon('heroicon-o-trash')
            ->color('danger');
    }

    public static function report(object $action): object
    {
        return $action
            ->icon('heroicon-o-flag')
            ->color('danger');
    }

    public static function whatsapp(object $action): object
    {
        return $action
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success');
    }
}
