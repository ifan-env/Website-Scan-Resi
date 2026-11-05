<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Inference Model
    |--------------------------------------------------------------------------
    |
    | Model SVM disimpan dalam format Python (.pkl), sehingga inference
    | dijalankan oleh microservice Flask. Laravel berkomunikasi lewat HTTP.
    |
    */

    'ml_url' => rtrim(env('ML_SERVICE_URL', 'http://127.0.0.1:5000'), '/'),

    'ml_timeout' => (int) env('ML_SERVICE_TIMEOUT', 30),

    'model_path' => base_path(env('MODEL_PATH', '../projek-semester5/model_svm_resi.pkl')),

    'dataset_path' => base_path(env('DATASET_PATH', '../projek-semester5/dataset_latih')),

    'classes' => [
        'ada_resi' => 'Ada Resi',
        'bukan_resi' => 'Bukan Resi',
        'tidak_ada_resi' => 'Tidak Ada Resi',
    ],

];
