<?php

    use Pecee\Http\Request;
    use Pecee\SimpleRouter\SimpleRouter as Router;

    use App\Middlewares\ProccessRawBody;
    use App\Middlewares\Authenticate;

    use App\Exceptions\CustomException;
    

    Router::group([
        'prefix' => '/api/',
        'middleware' => [ ProccessRawBody::class ]
        ], 
        function(){
            //запросы без авторизации по токену
            Router::post('/auth/sign-in',    'AuthController@signin');
            Router::post('/auth/sign-up',    'AuthController@signup');
            Router::post('/auth/refresh',    'AuthController@refresh');
            Router::post('/auth/recovery',   'AuthController@recovery');
            Router::post('/auth/changePass', 'AuthController@changePass');
            
            Router::group([
                'middleware' => [
                    Authenticate::class
                ]
            ], function () {
                //запросы с авторизацией
                Router::get ('/users/list', 'UserController@getUsersList');
                Router::post('/users/save', 'UserController@saveUser');
                Router::delete('/users/delete/{id}', 'UserController@deleteUser')->where(['id' => '[\d]+']);

                Router::get ('/items/list', 'ItemController@getItemsList');
                Router::get ('/items/info/{id}', 'ItemController@getItem')->where(['id' => '[\d]+']);
                Router::post('/items/save', 'ItemController@saveItem');
                Router::delete('/items/delete/{id}', 'ItemController@deleteItem')->where(['id' => '[\d]+']);
                Router::post('/files/upload/', 'FilesController@uploadFiles');
                Router::delete('/files/delete/', 'FilesController@deleteTmpFile');

                Router::get ('/orders/list', 'OrdersController@getOrdersList');
                Router::post('/orders/clear', 'OrdersController@clearOrders');
                Router::post('/orders/supplies/open', 'OrdersController@createSupply');
                Router::post('/orders/supplies/close', 'OrdersController@closeSupply');
                Router::post('/orders/supplies/picklist', 'OrdersController@createPickList');
                Router::get ('/supplies/list', 'OrdersController@getSuppliesList');

                Router::post('/timesheet', 'TimeSheetController@getTimeSheet');

                Router::post('/print/supply', 'PrintController@printSupply');
                Router::post('/print/picklist', 'PrintController@printPickList');
                Router::post('/print/stickers', 'PrintController@printStickers');
                Router::post('/print/stickersQR', 'PrintController@printStickersQR');
            });
        }
    );

    //вывод ошибок
    Router::error(function(Request $request, \Exception $exception)
    {
        $response = Router::response();
        if($exception instanceof CustomException){
            $code = $exception->getCode();
            if($code){
                $response->httpCode($code);
            }
            return $response->json($exception->output());
        }
    });
?>