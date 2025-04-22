<!-- Main Sidebar Container -->
<style>
    
</style>
<aside class="main-sidebar sidebar-light-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
        <!-- <img src="dist/img/WEBLAB.svg" alt="WEBLAB Logo" class="brand-image img-circle elevation-3"> -->
        
        <img src="dist/img/cliqproperty1.png" alt="Cliqproperty Logo" class="brand-image">
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        

        <!-- SidebarSearch Form -->
        

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
                <li class="nav-item active">
                    <a href="{{url('home')}}" class="nav-link">
                        <img src="dist/img/sidebarIcon/dashboard.svg" alt="" srcset="">
                        <p>Dashboards</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{url('company')}}" class="nav-link">
                        <i class="nav-icon fas fa-inbox"></i>
                        <p>
                            Company
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{url('company-admin')}}" class="nav-link">
                        <i class="nav-icon fas fa-inbox"></i>
                        <p>
                            Company Admin
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-home"></i>
                        <p>
                            ACL
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{url('menu')}}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Menu</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{url('module')}}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Modules</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{url('role')}}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Roles</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{url('userRole')}}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>User Roles</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a href="{{url('allUser')}}" class="nav-link">
                        <i class="nav-icon fas fa-user"></i>
                        <p>
                            Users
                        </p>
                    </a>
                </li>
                
                
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>