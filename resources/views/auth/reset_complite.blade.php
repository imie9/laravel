@extends('layouts.master')
@section('body')
    {!! Form::open() !!}
        @include('widgets.form._formitem_password', ['name' => 'password', 'title' => 'Пароль', 'placeholder' => 'Пароль'])
        @include('widgets.form._formitem_password', ['name' => 'password_confirm', 'title' => 'Подтверждение пароля', 'placeholder' => 'Пароль'])
        @include('widgets.form._formitem_btn_submit', ['title' => 'Подтвердить'])
    {!! Form::close() !!}
@stop