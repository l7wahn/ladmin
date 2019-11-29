@extends("la.layouts.app")

@section("contentheader_title")
	<a href="{{ url(config('laraadmin.adminRoute') . '/languages') }}">@tslt("Language")</a> :
@endsection
@section("contentheader_description", __t($language->$view_col))
@section("section", __t("Languages"))
@section("section_url", url(config('laraadmin.adminRoute') . '/languages'))
@section("sub_section", "Edit")

@section("htmlheader_title", __t("Languages Edit : ".$language->$view_col))

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
				{!! Form::model($language, ['route' => [config('laraadmin.adminRoute') . '.languages.update', $language->id ], 'method'=>'PUT', 'id' => 'language-edit-form']) !!}
					@la_form($module)
					
					{{--
					@la_input($module, 'name')
					@la_input($module, 'iso')
					--}}
                    <br>
					<div class="form-group">
						{!! Form::submit( 'Update', ['class'=>'btn btn-success']) !!} <button class="btn btn-default pull-right"><a href="{{ url(config('laraadmin.adminRoute') . '/languages') }}">@tslt("Cancel")</a></button>
					</div>
				{!! Form::close() !!}
			</div>
		</div>
	</div>
</div>

<div class="box">
	<div class="box-header">
		<h2>@tslt("Translate") </h2>  <a href="{{url(config('laraadmin.adminRoute') . '/languages/google_translate/'.$language->id)}}">@tslt("Translate with Google Translate")</a>
	</div>
	<div class="box-body">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<table class="table table-dark">
					<thead>						
						<th width="30%" colspan="2">@tslt("Source String")</th>
						<th>@tslt("Translation")</th>
					</thead>
					<tbody>
						@php $translation_row = 0; @endphp
												
						@foreach($language->nonTranslatedSources() as $source)
				
							<tr class="translation_row" id="translation_row_{{$translation_row}}">
								<td>
									
								</td>
								<th>{{$source->text}}</th>
								<td>
									<textarea name="source_{{$source->id}}" data-row="{{$translation_row}}" class="form-control" name="x" placeholder="@tslt("Insert your translation")"></textarea>
								</td>
							</tr>
							@php $translation_row++; @endphp
						@endforeach

						@foreach($language->translations()->with('sourceText')->get() as $translation)
				
							<tr class="translation_row" id="translation_row_{{$translation_row}}">
								<td>
									<span class="btn btn-success btn-xs btn-round"><i class="fa fa-check"></i></span>
								</td>
								<th>
									{{$translation->sourceText->text}}
								</th>
								<td>
									<textarea name="source_{{$translation->sourceText->id}}" data-row="{{$translation_row}}" class="form-control" name="x" placeholder="@tslt("Insert your translation")">{{$translation->text}}</textarea>
								</td>
							</tr>
							@php $translation_row++; @endphp
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
	var selected = -1;
	var changed = false;

	var edited = {};

	$("#language-edit-form").validate({
		
	});

	$("[data-row]").focus(function() {
		selectedRowBehaviour($(this).data('row'));		
	});

	$("[data-row]").change(() => changed = true);

	$("[data-row]").on("input", function() {
		changed = true;		
		saveTranslation($(this).data("row"));
	});

	$(window).keydown(function(e) {
		if(e.ctrlKey)
		{
			if(e.which == 83)
			{
				e.preventDefault();
				save();
			}
		}
	});

	$("[data-row]").keydown(function(e){
		

		if (e.ctrlKey) 
		{
			switch(e.which) {
				case 37: // left
				case 38: // up
					selectRow($(this).data("row") - 1);
					break;
				case 13:
				case 39: // right		
				case 40: // down
					selectRow($(this).data("row") + 1);
					break;
			}
		}	

		if (e.which === 9) {  
			e.preventDefault();
			selectRow($(this).data("row") + (e.shiftKey ? -1 : 1));
		}
	});

	$("[data-row]").blur(function() 
	{
		saveTranslation($(this).data("row"));
	});

	function selectRow(id)
	{
		$('[data-row="' + id + '"]').focus();
		selectedRowBehaviour(id);
	}

	function selectedRowBehaviour(id)
	{
		selected = id;
		var row = getTableRowById(id);
		$(".translation_row").removeClass("success");		
		row.addClass("success");
	}

	function getTableRowById(id)
	{
		var prefix = "translation_row_";
		var row = $("#" + prefix + id);
		return row;
	}

	function saveTranslation(id)
	{
		if(changed)
		{
			var row = $('[data-row="' + id + '"]');
			getTableRowById(id).addClass("warning");
			edited[row.attr("name")] = row.val();
			changed = false;
		}
	}

	function save() 
	{
		console.log(edited);
		if(Object.keys(edited).length == 0)
		{
			console.log("nothing to submit");
			return;
		}

		var form = $('#language-edit-form').clone().hide().attr('id', "");
		for(key in edited)
		{
			form.append('<input name="translations['+ key.replace("source_",'') +']" value="' + edited[key] + '">');			
		}
		
		$("body").prepend(form);
		
		form.submit();
	}
	
});
</script>
@endpush
