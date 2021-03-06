<?php

namespace App\Controllers;

use CQ\DB\DB;
use CQ\Helpers\App;
use CQ\Helpers\Request;
use CQ\Helpers\Session;
use CQ\Helpers\File;
use CQ\Response\Json;
use CQ\Response\Twig;
use CQ\Config\Config;
use CQ\Controllers\Controller;
use App\Helpers\MailHelper;

class SendController extends Controller
{
    /**
     * Show mail sent successfully page
     *
     * @return Html`
     */
    public function success()
    {
        $redirect = Session::get('redirect');
        Session::unset('redirect');

        return $this->respond('success.twig', [
            'redirect' => $redirect,
        ]);
    }

    /**
     * Set CORS headers for allowed domains
     *
     * @param string $id
     *
     * @return Json
     */
    public function cors($id)
    {
        $site = DB::get('sites', ['domain'], [
            'id' => $id,
        ]);

        if (!$site) {
            return $this->respondJson(
                'Invalid sitekey',
                [],
                401
            );
        }

        if (App::debug()) {
            return new Json([
                'success' => true,
                'message' => "DEBUG MODE: CORS allowed for *",
                'data' => [],
            ], 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => 'Content-Type',
                'Access-Control-Allow-Methods' => 'OPTIONS, POST',
            ]);
        }

        return new Json([
            'success' => true,
            'message' => "CORS allowed for {$site['domain']}",
            'data' => [],
        ], 200, [
            'Access-Control-Allow-Origin' => "https://{$site['domain']}",
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Access-Control-Allow-Methods' => 'OPTIONS, POST',
        ]);
    }

    /**
     * Send mail from FormData.
     *
     * @param object $request
     *
     * @return Json|Redirect
     */
    public function form($request, $id)
    {
        return $this->handleSubmission($request, $id);
    }

    /**
     * Send mail from JSON data.
     *
     * @param object $request
     *
     * @return Json
     */
    public function api($request, $id)
    {
        return $this->handleSubmission($request, $id);
    }

    /**
     * Handle submission for both types of data
     *
     * @param object $request
     * @param string $id
     *
     * @return void
     */
    private function handleSubmission($request, $id)
    {
        $site = DB::get('sites', ['name', 'user_email'], [
            'id' => $id,
        ]);

        if (!$site) {
            return $this->respondJson(
                'Invalid sitekey',
                [],
                401
            );
        }

        $template = new File('../views/mail.twig');
        $app_name = Config::get('app.name');
        $template_data = (array) $request->data;
        unset($template_data['redirect']);

        try {
            MailHelper::send(
                $site['user_email'],
                "[{$app_name}] {$request->data->subject}",
                Twig::renderFromText($template->read(), [
                    'name' => $site['name'],
                    'data' => $template_data,
                ]),
                Config::get('app.name'),
                $request->data->email
            );
        } catch (\Throwable $th) {
            return $this->respondJson(
                'Mail Error',
                $th->getMessage(),
                400
            );
        }

        if (Request::isForm($request)) {
            if ($request->data->redirect) {
                Session::set('redirect', $request->data->redirect);
            }

            return $this->redirect('/form/success');
        }

        return new Json([
            'success' => true,
            'message' => 'Mail sent',
            'data' => [],
        ], 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Content-Type',
            'Access-Control-Allow-Methods' => 'OPTIONS, POST',
        ]);
    }
}
