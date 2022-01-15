<?php
require "autoload.php";
use Vipkwd\Utils\Dev;
use Vipkwd\Utils\Position;

Dev::dump(Position::getDistance());
Dev::dump(Position::getDistance( 120.149911, 30.282324, 120.155428, 30.244007 ));
Dev::dump(Position::getDistance( 112.45972, 23.05116, 103.850070, 1.289670 ));

Dev::dump(Position::merchantRadiusAxies( 112.45972, 23.05116 ));

// lt -> rb
Dev::vdump("lt -> rb: ".Position::getDistance( 112.43043206369, 23.078109458524, 112.48900793631, 23.024210541476 ));
Dev::vdump("lt -> rt: ".Position::getDistance( 112.43043206369, 23.078109458524, 112.48900793631, 23.078109458524 ));
Dev::vdump("lt -> lb: ".Position::getDistance( 112.43043206369, 23.078109458524, 112.43043206369, 23.024210541476 ));

Dev::vdump("lb -> rt: ".Position::getDistance( 112.43043206369, 23.024210541476, 112.48900793631, 23.078109458524 ));

Dev::vdump("O -> lt: ".Position::getDistance( 112.45972, 23.05116 ,112.43043206369, 23.078109458524));