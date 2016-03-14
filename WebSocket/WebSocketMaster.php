<?php
namespace Hyperframework\WebSocket;

abstract class WebSocketMaster {
    abstract public function run();

    public function getClientId() {
    }

    public function getInput() {
    }

    public function openInputStream() {
    }
}
