<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 16.07.2016
 */
namespace v3toys\skeeks\controllers;
use skeeks\cms\base\Controller;
use skeeks\cms\helpers\RequestResponse;
use v3toys\skeeks\models\V3toysOrder;
use v3toys\skeeks\V3toysModule;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

/**
 * Class CartController
 * @package v3toys\skeeks\controllers
 */
class CartController extends Controller
{

    /**
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [

            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'validate'  => ['post'],
                ],
            ],
        ]);
    }


    public function actionIndex()
    {
        return $this->render($this->action->id);
    }

    /**
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionFinish()
    {
        if (!$key = \Yii::$app->request->get('key'))
        {
            throw new NotFoundHttpException('Заказ не найден');
        }

        if (!$v3toysOrder = V3toysOrder::findOne(['key' => $key]))
        {
            throw new NotFoundHttpException("Заказ #{$key} не найден");
        }

        return $this->render($this->action->id, ['model' => $v3toysOrder]);
    }

    /**
     * @return string
     */
    public function actionCheckout()
    {
        $this->view->title = \Yii::t('skeeks/shop/app', 'Checkout').' | '.\Yii::t('skeeks/shop/app', 'Shop');

        $v3toysOrder = V3toysOrder::createCurrent();
        $v3toysOrder->loadDefaultValues();

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            if ($v3toysOrder->load(\Yii::$app->request->post()) && $v3toysOrder->save())
            {
                //create order
                /*try
                {*/
                    //$order = $v3toysOrder->processCreateOrder();

                    $rr->message = 'Заказ успешно создан';
                    $rr->success = true;
                    $rr->redirect = Url::to(['/v3toys/cart/finish', 'key' => $v3toysOrder->key]);

                /*} catch (\Exception $e)
                {
                    $rr->message = "Ошибка создания заказа: " . $e->getMessage();
                    $rr->success = false;

                    \Yii::error($rr->message, V3toysModule::className());
                }*/
            } else
            {
                $rr->message = 'Проверьте правильность заполнения полей';
                $rr->success = false;
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'model' => $v3toysOrder
        ]);
    }


    /**
     * @return string
     */
    public function actionCheckoutValidate()
    {
        $rr = new RequestResponse();
        $v3toysOrder = V3toysOrder::createCurrent();
        return $rr->ajaxValidateForm($v3toysOrder);
    }
}