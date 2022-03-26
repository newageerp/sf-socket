<?php
namespace Newageerp\SfSocket\Event;

use Symfony\Contracts\EventDispatcher\Event;

class SocketSendPoolEvent extends Event
{
    public const NAME = 'sfsocket.sendpool';
}