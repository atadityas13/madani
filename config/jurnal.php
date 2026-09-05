<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Jurnal write switch
    |--------------------------------------------------------------------------
    |
    | Set false during cutover until history import is verified, then true.
    | Reads (list/entries/cetak) stay available when writes are disabled.
    |
    */
    'writes_enabled' => (bool) env('JURNAL_WRITES_ENABLED', true),

];
