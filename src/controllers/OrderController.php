<?php
/**
 * Mailchimp for Craft Commerce
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\mc\controllers;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use ether\mc\MailchimpCommerce;
use Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class OrderController
 *
 * @author  Ether Creative
 * @package ether\mc\controllers
 */
class OrderController extends Controller
{

	protected $allowAnonymous = true;

	/**
	 * Attempts to restore an abandoned cart
	 *
	 * @return Response
	 * @throws MissingComponentException
	 * @throws BadRequestHttpException
	 */
	public function actionRestore ()
	{
		$commerce = Commerce::getInstance();
		$settings = MailchimpCommerce::getInstance()->getSettings();
		$session = Craft::$app->getSession();

		$number = Craft::$app->getRequest()->getRequiredQueryParam('number');
		$cid = Craft::$app->getRequest()->getQueryParam('mc_cid');
		$order = $commerce->getOrders()->getOrderByNumber($number);

		if (!$order)
		{
			$session->setError($settings->expiredCartError);
			return $this->redirect($settings->abandonedCartRestoreUrl);
		}

		if ($order->isCompleted)
		{
			$session->setError($settings->completedCartError);
			return $this->redirect($settings->abandonedCartRestoreUrl);
		}

		if ($cid)
		{
			try
			{
				Craft::$app->getDb()->createCommand()
					->update(
						'{{%mc_orders_synced}}',
						['cid' => $cid],
						['orderId' => $order->id],
						[],
						false
					)->execute();
			}
			catch (Exception $e)
			{
				Craft::error($e, 'mailchimp-commerce');
			}
		}

		$commerce->getCarts()->forgetCart();
		$session->set('commerce_cart', $number);

		$session->setNotice($settings->cartRestoredNotice);
		return $this->redirect($settings->abandonedCartRestoreUrl);
	}

}
