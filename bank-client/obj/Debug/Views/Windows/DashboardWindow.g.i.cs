﻿#pragma checksum "..\..\..\..\Views\Windows\DashboardWindow.xaml" "{8829d00f-11b8-4213-878b-770e8597ac16}" "686F5E309F8F5CECB67D719640393C1981371D105C1C160B5C7CF7BA5737139D"
//------------------------------------------------------------------------------
// <auto-generated>
//     This code was generated by a tool.
//     Runtime Version:4.0.30319.42000
//
//     Changes to this file may cause incorrect behavior and will be lost if
//     the code is regenerated.
// </auto-generated>
//------------------------------------------------------------------------------

using System;
using System.Diagnostics;
using System.Windows;
using System.Windows.Automation;
using System.Windows.Controls;
using System.Windows.Controls.Primitives;
using System.Windows.Data;
using System.Windows.Documents;
using System.Windows.Ink;
using System.Windows.Input;
using System.Windows.Markup;
using System.Windows.Media;
using System.Windows.Media.Animation;
using System.Windows.Media.Effects;
using System.Windows.Media.Imaging;
using System.Windows.Media.Media3D;
using System.Windows.Media.TextFormatting;
using System.Windows.Navigation;
using System.Windows.Shapes;
using System.Windows.Shell;


namespace bank_api.Views.Windows {
    
    
    /// <summary>
    /// DashboardWindow
    /// </summary>
    public partial class DashboardWindow : System.Windows.Window, System.Windows.Markup.IComponentConnector {
        
        
        #line 121 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Performance", "CA1823:AvoidUnusedPrivateFields")]
        internal System.Windows.Controls.TextBlock WelcomeTextBlock;
        
        #line default
        #line hidden
        
        
        #line 124 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Performance", "CA1823:AvoidUnusedPrivateFields")]
        internal System.Windows.Controls.TextBlock BalanceTextBlock;
        
        #line default
        #line hidden
        
        
        #line 135 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Performance", "CA1823:AvoidUnusedPrivateFields")]
        internal System.Windows.Controls.DataGrid TransactionsDataGrid;
        
        #line default
        #line hidden
        
        private bool _contentLoaded;
        
        /// <summary>
        /// InitializeComponent
        /// </summary>
        [System.Diagnostics.DebuggerNonUserCodeAttribute()]
        [System.CodeDom.Compiler.GeneratedCodeAttribute("PresentationBuildTasks", "4.0.0.0")]
        public void InitializeComponent() {
            if (_contentLoaded) {
                return;
            }
            _contentLoaded = true;
            System.Uri resourceLocater = new System.Uri("/bank-api;component/views/windows/dashboardwindow.xaml", System.UriKind.Relative);
            
            #line 1 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
            System.Windows.Application.LoadComponent(this, resourceLocater);
            
            #line default
            #line hidden
        }
        
        [System.Diagnostics.DebuggerNonUserCodeAttribute()]
        [System.CodeDom.Compiler.GeneratedCodeAttribute("PresentationBuildTasks", "4.0.0.0")]
        [System.ComponentModel.EditorBrowsableAttribute(System.ComponentModel.EditorBrowsableState.Never)]
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Design", "CA1033:InterfaceMethodsShouldBeCallableByChildTypes")]
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Maintainability", "CA1502:AvoidExcessiveComplexity")]
        [System.Diagnostics.CodeAnalysis.SuppressMessageAttribute("Microsoft.Performance", "CA1800:DoNotCastUnnecessarily")]
        void System.Windows.Markup.IComponentConnector.Connect(int connectionId, object target) {
            switch (connectionId)
            {
            case 1:
            this.WelcomeTextBlock = ((System.Windows.Controls.TextBlock)(target));
            return;
            case 2:
            this.BalanceTextBlock = ((System.Windows.Controls.TextBlock)(target));
            return;
            case 3:
            
            #line 128 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
            ((System.Windows.Controls.Button)(target)).Click += new System.Windows.RoutedEventHandler(this.WithdrawButton_Click);
            
            #line default
            #line hidden
            return;
            case 4:
            
            #line 129 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
            ((System.Windows.Controls.Button)(target)).Click += new System.Windows.RoutedEventHandler(this.DepositButton_Click);
            
            #line default
            #line hidden
            return;
            case 5:
            
            #line 130 "..\..\..\..\Views\Windows\DashboardWindow.xaml"
            ((System.Windows.Controls.Button)(target)).Click += new System.Windows.RoutedEventHandler(this.RefreshButton_Click);
            
            #line default
            #line hidden
            return;
            case 6:
            this.TransactionsDataGrid = ((System.Windows.Controls.DataGrid)(target));
            return;
            }
            this._contentLoaded = true;
        }
    }
}

