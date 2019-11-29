@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/texts') }}">@tslt("Text")</a> :
@endsection
@section("contentheader_description", __t($text->$view_col))
@section("section", __t("Texts"))
@section("section_url", url(config('laraadmin.adminRoute') . '/texts'))
@section("sub_section", "Edit")

@section("htmlheader_title", __t("Texts Edit : ".$text->$view_col))

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
				{!! Form::model($text, ['route' => [config('laraadmin.adminRoute') . '.texts.update', $text->id ], 'method'=>'PUT', 'id' => 'text-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'text')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/texts') }}">@tslt("Cancel")</a></button>
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
	$("#text-edit-form").validate({
		
	});
});
</script>
@endpush
