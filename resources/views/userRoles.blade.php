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
                            <h1>User Roles</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="home.php">Home</a></li>
                                <li class="breadcrumb-item active">User Roles</li>
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
                                    Add User Roles
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <form class="form-horizontal" name="company" id="company">
                                        <div class="mb-3">
                                            <Label for="menu_title" class="form-label">
                                                User
                                            </Label>
                                            <select name="user" id="user" type="text" class="form-control">
                                                <option value="">--select User--</option>
                                                @foreach ($users as $item)
                                                    <option value="{{ $item->id }}">{{ $item->first_name }}
                                                        {{ $item->last_name }}</option>
                                                @endforeach
                                            </select>

                                        </div>
                                        <div class="mb-3">
                                            <Label for="slug" class="form-label">
                                                Role
                                            </Label>
                                            <select name="role" id="role" type="text" class="form-control">
                                                <option value="">--select Role--</option>
                                                @foreach ($roles as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                @endforeach

                                            </select>
                                        </div>

                                        <div class="mt-3">
                                            <button class="btn btn-primary w-md" type="button" id="add">
                                                Submit
                                            </button>
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
                                    Users roles List
                                </div>
                                <!-- /.card-header -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="example1" class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>User Name</th>
                                                    <th>Roles</th>
                                                    <th>company Name</th>
                                                    <th>Email</th>
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
            // var url = "http://localhost:8000/api/userRoles";
            var url = "{{ config('app.api_url') }}" + "userRoles";
            var companyData = "";

            console.log(authUsers);
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
                    data.data.forEach((item, key) => {
                        companyData += "<tr>" +
                            "<th scope='row'>" + (key + 1) + "</th>" +
                            "<td>" + item.first_name + " " + item.last_name + "</td>" +
                            "<td>";
                        item.roles.forEach((item, key) => {
                            companyData +=
                                "<span class='badge badge-primary' onclick='userRoleDelete(" +
                                item.id + ")'" +
                                ">" + item.role.name + "</span><br>";
                        });
                        companyData += "</td><td>";
                        if (item.company != null) {
                            companyData +=
                                "<span class='badge badge-success' onclick='userRoleDelete(" +
                                item.id + ")'" +
                                ">" + item.company.company_name + "</span><br></td>";
                        }
                        companyData += "</td>" +
                            "<td>" + item.email + "</td>" +
                            "</tr>";


                    });
                    if (companyData != "") {
                        $("#comData").html(companyData);

                        $('#example1').DataTable();
                    }


                }
            });

        }

        function userRoleDelete(id) {

            var formData = {
                id: id

            };

            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                url: "{{ config('app.api_url') }}" + "userRolesDelete",
                data: formData,
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $('#company').trigger("reset");
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        $("#add").on("click", addRecord);

        function addRecord() {

            var menu_title = $("#user").val();
            var slug = $("#role").val();



            if (menu_title == "") {
                toastr.error("Enter a user");
                return false;
            }
            if (slug == "") {
                toastr.error("role cannot be empty");
                return false;
            }



            // get values
            $.ajax({

                method: "post",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                // url: "http://localhost:8000/api/roleAssignUserInsertAjax",
                url: "{{ config('app.api_url') }}" + "roleAssignUserInsertAjax",
                datatype: "html",
                data: $("#company").serialize(),
                success: function(data) {
                    toastr.success('Success');
                    loadCompany();
                    $('#company').trigger("reset");
                },
                error: function(data) {
                    toastr.error(data);
                }
            });
        }

        function deleteCompany(id) {
            // var url = "http://localhost:8000/api/menus/" + id;
            var url = "{{ config('app.api_url') }}" + "menus/" + id;
            $.ajax({
                method: "delete",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),

                    "Authorization": "Bearer " + authUsers.token,
                },
                success: function(data) {
                    toastr.success('Menu is Deleted');
                    loadCompany();

                },
                error: function(data) {
                    toastr.error('Menu can not Deleted');
                }
            });
        }
    </script>
</body>

</html>
