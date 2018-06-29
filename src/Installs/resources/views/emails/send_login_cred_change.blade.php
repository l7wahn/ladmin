@tslt("Dear") {{ $user->name }},<br><br>

@tslt("Your login credentials are changed"):<br><br>

@tslt("Username"): {{ $user->email }}<br>
@tslt("password"): {{ $password }}<br><br>

@tslt("You can login on") <a href="{{ url('/login') }}">{{ str_replace("http://", "", url('/login')) }}</a>.<br><br>

@tslt("Best Regards"),