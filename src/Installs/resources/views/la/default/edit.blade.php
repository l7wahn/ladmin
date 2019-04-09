@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/'.strtolower($module->name)) }}">@tslt($module->name)</a> :
@endsection
@section("contentheader_description", __t($instance->$view_col))
@section("section", __t($module->name))	
@section("section_url", url(config('laraadmin.adminRoute') . '/'.strtolower($module->name)))
@section("sub_section", "Edit")

@section("htmlheader_title", __t($module->name." Edit : ".$instance->$view_col))

@section("main-content")

@if (count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box">
	<div class="box-header">
		
	</div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				{!! Form::model($instance, ['route' => [config('laraadmin.adminRoute') . '.'.strtolower($module->name).'.update', $instance->id ], 'method'=>'PUT', 'id' => strtolower($module->name).'-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'test_string')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/'.strtolower($module->name)) }}">@tslt("Cancel")</a></button>
					</div>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
	$("#".strtolower($module->name)."-edit-form").validate({
		
	});
});
</script>
@endpush
