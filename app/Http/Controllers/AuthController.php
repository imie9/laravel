<?php

namespace App\Http\Controllers;

use Cartalyst\Sentinel\Checkpoints\NotActivatedException;
use Cartalyst\Sentinel\Checkpoints\ThrottlingException;

use Cartalyst\Support\Mailer;
use Illuminate\Http\Request;
use App\Http\Requests;
use Redirect;
use Sentinel;
use Activation;
use Reminder;
use Validator;
use Mail;
use Storage;
use CurlHttp;


class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function register()
    {
        return view('auth.register');
    }

    public function wait()
    {
        return view('auth.wait');
    }

    public function loginProcess(Request $request)
    {
        try
        {
            $this->validate($request, [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );
            $remember = (bool) $request->remember;
            if(Sentinel::authenticate($request->all(), $remember))
            {
                return Redirect::intended('/');
            }
            $errors = 'Неправильный логин или пароль';
            return Redirect::back()
                ->withInput()
                ->withErrors($errors);
        }
        catch (NotActivatedException $e)
        {
            $sentuser = $e->getUser();
            $activation = Activation::create($sentuser);
            $code = $activation->code;
            $sent = Mail::send('mail,account_activate', compact('sentuser', 'code'), function($m) use ($sentuser)
            {
               $m->from('asde54@yandex.ru', 'laravel');
               $m->to($sentuser->email)->subject('Активация аккаунта');
            });
            if($sent === 0)
            {
                return Redirect::to('login')
                    ->withErrors('Ошибка отправки письма активации');
            }
            $errors = 'Ваш аккаунт не ативирован! Поищите в своем почтовом ящике письмо со ссылкой для активации (Вам отправлено повторное письмо). ';
            return view('auth.login')->withErrors($errors);
        }
        catch (ThrottlingException $e)
        {
            $delay = $e->getDelay();
            $errors = "Ваш аккаунт блокирована на {$delay} секунд";
        }
        return Redirect::back()
            ->withInput()
            ->withErrors($errors);
    }

    public function registerProcess(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
            'password_confirm' => 'required|same::password'
        ]);
        $input = $request->all();
        $credentials = ['email' => $request->email];
        if($user = Sentinel::findByCredentials($credentials))
        {
            return Redirect::to('register')
                ->withErrors('Такой email уже зарегистрирован');
        }

        if ($user = Sentinel::register($input))
        {
            $activation = Activation::create($sentuser);
            $code = $activation->code;
            $sent = Mail::send('mail.account_activate', compact('sentuser', 'code'), function($m) use ($sentuser)
            {
                $m->from('asde95@yandex.ru', 'laravel');
                $m->to($sentuser->email)->subject('Активация аккаунта');
            });
            if ($sent === 0)
            {
                return Redirect::to('register')
                    ->withErrors('Ошибка отправки письма активации');
            }
            $role = Sentinel::findRoleBySlug('user');
            $role->users()->attach($sentuser);

            return Redirect::to('login')
                ->withSuccess('Ваш аккаунт создан, проверьте почту')
                ->with('userId', $sentuser->getUserId());
        }
        return Redirect::to('register')
            ->withInput()
            ->withErrors('Failed to register.');
    }

    public function activate($id, $code)
    {
        $sentinel = Sentinel::findById($id);

        if (!Activation::complete($sentuser, $code))
        {
            return Redirect::to('login')
                ->withErrors('Неверный или просроченный код активации');
        }
        return Redirect::to('login')
            -withSuccess('Аккаунт активирован');
    }

    public function resetOrder()
    {
        return view('auth.reset_order');
    }

    public function resetOrderProcess(Request $request)
    {
        $this->validate($request, [
           'email' => 'required|email'
        ]);
        $email = $request->email;
        $sentuser = Sentinel::findByCredentials(compact('email'));

        if(!$sentuser)
        {
            return Redirect::back()
                ->withInput()
                ->withErrors('Пользователь с таким email в системе уже найден');
        }
        $reminder = Reminder::exists($sentuser) ?: Reminder::create($sentuser);
        $code = $reminder->code;

        $sent = Mail::send('mail.account_reminder', compact('sentuser', 'code'), function($m) use ($sentuser)
        {
            $m->from('asde95@yandex.ru', 'laravel');
            $m->to($sentuser->email)->subject('Сброс пароля');
        });

        if ($sent === 0)
        {
            return Redirect::to('reset')
                -withErrors('Ошибка отправки email');
        }
        return Redirect::to('wait');
    }

    public function resetComplete($id, $code)
    {
        $user = Sentinel::findUserById($id);
        return view('auth.reset_complete');
    }

    public function resetCompleteProcess(Request $request, $id, $code)
    {
        $this->validate($request, [
           'password' => 'required',
           'password_confirm' => 'required|same::password'
        ]);
        $user = Sentinel::findUserById($id);
        if(!$user)
        {
            return Redirect::back()
                ->withInput()
                ->withErrors('Такого пользователя не существует');
        }
        if (!Reminder::complete($user, $code, $request->password))
        {
            return Redirect::to('login')
                ->withErrors('Неверный или просроченный код сброса пароля');
        }
        return Redirect::to('login')->withSuccess('Пароль сброшен.');
    }

    public function logoutuser()
    {
        Sentinel::logout();
        return Redirect::intended('/');
    }

}
