@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/translations') }}">@tslt("Translation")</a> :
@endsection
@section("contentheader_description", __t($translation->$view_col))
@section("section", __t("Translations"))
@section("section_url", url(config('laraadmin.adminRoute') . '/translations'))
@section("sub_section", "Edit")

@section("htmlheader_title", __t("Translations Edit : ".$translation->$view_col))

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
				{!! Form::model($translation, ['route' => [config('laraadmin.adminRoute') . '.translations.update', $translation->id ], 'method'=>'PUT', 'id' => 'translation-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'language_id')
					@la_input($module, 'text_id')
					@la_input($module, 'text')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/translations') }}">@tslt("Cancel")</a></button>
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
	$("#translation-edit-form").validate({
		
	});
});
</script>
@endpush
