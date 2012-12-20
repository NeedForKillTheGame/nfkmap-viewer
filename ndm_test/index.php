<?php

$filename = "39564_[D.o.$]v-D-v_&_Tarpid_vs_[S]_&_[D.o.$]Kain._on_ctf2_CTF_20-16-38.ndm";

$content = file_get_contents($filename);

$data_bz = substr($content, 8, strlen($content) - 8 );
$data_bin = bzdecompress($data_bz);

file_put_contents("test2.ndm", $data_bin);