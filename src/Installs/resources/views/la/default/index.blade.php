@extends("la.layouts.app")

@section("contentheader_title", __t($module->name))
@section("contentheader_description", __t($module->name." listing"))
@section("section", __t($module->name))
@section("sub_section", __t("Listing"))
@section("htmlheader_title", __t($module->name." Listing"))

@section("headerElems")
@la_access($module->name, "create")
	<button class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#AddModal">@tslt("Add") {{__t($module->name)}}</button>
@endla_access
@endsection

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

<div class="box box-success">
	<div class="box-body">
		<table id="example1" class="table table-bordered">
		<thead>
		<tr class="success">
			@foreach( $listing_cols as $col )
			<th>{{ isset($module->fields[$col]['label']) ? __t($module->fields[$col]['label']) : __t(ucfirst($col)) }}</th>
			@endforeach
			@if($show_actions)
			<th>@tslt("Actions")</th>
			@endif
		</tr>
		</thead>
		<tbody>
			
		</tbody>
		</table>
	</div>
</div>

@la_access($module->name, "create")
<div class="modal fade" id="AddModal" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">@tslt("Add") {{__t($module->name)}}</h4>
			</div>
			{!! Form::open(['action' => 'LA\\'.$module->controller.'@store', 'id' => strtolower($module->name).'-add-form']) !!}
			<div class="modal-body">
				<div class="box-body">
                    @la_form($module)
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">@tslt("Close")</button>
				{!! Form::submit( 'Submit', ['class'=>'btn btn-success']) !!}
			</div>
			{!! Form::close() !!}
		</div>
	</div>
</div>
@endla_access

@endsection

@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('la-assets/plugins/datatables/datatables.min.css') }}"/>
@endpush

@push('scripts')
<script src="{{ asset('la-assets/plugins/datatables/datatables.min.js') }}"></script>
<script>
$(function () {
	$("#example1").DataTable({
		processing: true,
        serverSide: true,
        ajax: "{{ url(config('laraadmin.adminRoute') . '/'.$module->name_db.'_dt_ajax') }}",
		language: {
			lengthMenu: "_MENU_",
			search: "_INPUT_",
			searchPlaceholder: "@tslt("Search")"
		},
		@if($show_actions)
		columnDefs: [ { orderable: false, targets: [-1] }],
		@endif
	});
	$("#{{strtolower($module->name)}}-add-form").validate({
		
	});
});
</script>
@endpush