<?php

try {
    return [
        'callback_status' => 'ok'
    ];
} catch (Exception $e){
    //todo log
    echo json_encode(['error' => $e->getMessage()]);
}