<?php
namespace AesirxAnalytics\Route\Middleware;


use Pecee\Http\Middleware\IMiddleware;
use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\HttpException;

class IsBackendMiddlware implements IMiddleware {

	/**
	 * @param Request $request
	 */
	public function handle( Request $request ): void {
		if (!current_user_can( 'administrator'))
		{
			throw new HttpException("Permission denied!", 403);
		}
	}
}
