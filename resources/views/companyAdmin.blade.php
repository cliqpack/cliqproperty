<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Header css -->
    @include('includes/head')
    @include('includes/head-table')

</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Nab content -->
        @include('includes/header')
        <!-- sidebar content -->
        @include('includes/sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Company Admin</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">Company</li>
                            </ol>
                        </div>
                    </div>
                </div><!-- /.container-fluid -->
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Add Property Manager
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form class="form-horizontal row" name="company" id="company">
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="first_name" class="form-label">
                                                    First Name
                                                </Label>
                                                <Input name="first_name" id="first_name" type="text"
                                                    class="form-control" />

                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="last_name" class="form-label">
                                                    Last Name
                                                </Label>
                                                <Input name="last_name" id="last_name" type="text"
                                                    class="form-control" />

                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="email" class="form-label">
                                                    Email
                                                </Label>
                                                <Input name="email" id="email" type="text"
                                                    class="form-control" />
                                                <Input name="user_type" id="user_type" type="hidden"
                                                    value="Property Manager" class="form-control" />

                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="mb-3">
                                                <Label for="address" class="form-label">
                                                    Company
                                                </Label>
                                                <input type="hidden" name="user_type" id="user_type"
                                                    value="Property Manager">
                                                <select name="company_id" id="company_id" type="text"
                                                    class="form-control">
                                                    <option value="">--select Company--</option>
                                                    @foreach ($companies as $company)
                                                        <option value="{{ $company->id }}">{{ $company->company_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-2">
                                            <div style="margin-top: 2rem;">
                                                <button type="button" class="btn btn-primary" data-toggle='modal'
                                                    data-target="#modal-default">add</button>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="password" class="form-label">
                                                    Password
                                                </Label>
                                                <input name="password" id="password" type="text"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="confirm_password" class="form-label">
                                                    Address
                                                </Label>
                                                <input name="address" id="address" type="text"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="mobile_phone" class="form-label">
                                                    Mobile Phone
                                                </Label>
                                                <input name="mobile_phone" id="mobile_phone" type="text"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="mb-3">
                                                <Label for="work_phone" class="form-label">
                                                    Work Phone
                                                </Label>
                                                <input name="work_phone" id="work_phone" type="text"
                                                    class="form-control" />
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mt-3">
                                                <button class="btn btn-primary w-md" type="button" id="add">
                                                    Submit
                                                </button>

                                                <div class="d-flex justify-content-center">
                                                    <div class="spinner-border text-primary m-1" role="status"
                                                        id="loader_1" style="display: none;">
                                                        <span class="sr-only">Loading...</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-header">
                                    Company Manager List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="example1" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>email</th>
                                                    <th>Company</th>
                                                    <th>type</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="comData">

                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                    <th></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <!-- /.card-body -->
                            </div>
                            <!-- /.card -->
                        </div>
                    </div>
                </div>
            </section>
            <!--modal-content -->
            <div class="modal fade" id="modal-default">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Add Company</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <form id="company_frm" name="company_frm" class="form-horizontal">
                                <div class="mb-3"><label for="company"
                                        class="form-label form-label">Company</label>
                                    <input name="company_name" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="address"
                                        class="form-label form-label">Address</label>
                                    <input name="address" type="text" class="form-control" />
                                </div>
                                <div class="mb-3"><label for="phone" class="form-label form-label">Phone</label>
                                    <input name="phone" type="text" class="form-control" />
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-primary"
                                        onclick="addRecord1()">Save</button>
                                </div>
                            </form>

                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
            <!-- /.modal -->
        </div>

        <!-- Footer content -->
        @include('includes/footer')
        <!-- Footer js -->
        @include('includes/foot')
        @include('includes/foot-table')
        <!-- Bootstrap 4 -->
        <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
        <!-- AdminLTE App -->
        <script src="dist/js/adminlte.js"></script>
        <!-- AdminLTE for demo purposes -->
        <script src="dist/js/demo.js"></script>
    </div>
    <script>
        var authUsers = JSON.parse(localStorage.getItem("authUser"));
        $(window).on('load', () => {
            loadCompany();
        })

        function loadCompany() {
            // var url = "http://localhost:8000/api/companies";
            var url = "{{ config('app.api_url') }}" + "all-manager";
            var companyData = "";
            $.ajax({

                method: "get",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Content-Type": "application/json",

                    "Access-Control-Allow-Origin": "*",

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    data.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.first_name + " " + item.last_name + "</td>" +
                            "<td>" + item.email + "</td>" +
                            "<td>" + item?.company?.company_name + "</td>" +
                            "<td>" + item.user_type + "</td>" +
                            "<td>" +
                            "<button type='button' class='btn btn-danger w-md' onClick='deleteCompany(" +
                            item.id + ")'>Delete</button>" +
                            "</td>" +
                            "</tr>";
                    });
                    if (companyData != "") {
                        $("#comData").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }
        $("#add").on("click", addRecord);

        function addRecord() {
            $("#add").hide();
            $("#loader_1").show();
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/companiesAdd",
                url: "{{ config('app.api_url') }}" + "register",
                datatype: "html",
                data: $("#company").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $("#add").show();
                    $("#loader_1").hide();
                    $('#company').trigger("reset");
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }


        function addRecord1() {

            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/companiesAdd",
                url: "{{ config('app.api_url') }}" + "companies",
                datatype: "html",
                data: $("#company_frm").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    window.location.reload();
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/companies/" + id;
            var url = "{{ config('app.api_url') }}" + "companies/" + id;
            $.ajax({
                method: "delete",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Content-Type": "application/json",

                    "Access-Control-Allow-Origin": "*",

                    "Authorization": "Bearer " + authUsers.token,

                },
                success: function(data) {
                    toastr.success('Company is Deleted');
                    loadCompany();

                },
                error: function(data) {
                    toastr.error('Company can not Deleted');
                }
            });
        }
    </script>
</body>

</html>
