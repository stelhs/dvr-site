<?php

function conf_dvr()
{
    return ['video_file_duration' => 60,
            'cameras' => [
                            ['name' => "from_lamp_post",
                             'desc' => "со столба основная",
                             'recording' => true,
                             'private' => false,
                            ],

                            ['name' => "south",
                             'desc' => "Юг",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "workshop_entrance",
                             'desc' => "Площадка у мастерской",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "toilet",
                             'desc' => "Толчок",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "west",
                             'desc' => "Запад с мастерской",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "west_post",
                             'desc' => "Запад со столба",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "east",
                             'desc' => "Восток",
                             'recording' => true,
                             'private' => false,
                             ],

                            ['name' => "workshop_1",
                             'desc' => "мастерская угол у ВРУ",
                             'recording' => true,
                             'private' => true,
                             ],

                            ['name' => "workshop_2",
                             'desc' => "мастерская угол у ворот",
                             'recording' => true,
                             'private' => true,
                             ],

                            ['name' => "workshop_3",
                             'desc' => "мастерская второй этаж",
                             'recording' => true,
                             'private' => true,
                            ],

                            ['name' => "north-gate",
                             'desc' => "калитка на севере",
                             'recording' => true,
                             'private' => false,
                            ],
                    ],
            ];
}