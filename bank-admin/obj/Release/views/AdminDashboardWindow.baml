   M S B A M L   `   `   `  ����   bank-api-admin-    'BankApiAdmin.Views.AdminDashboardWindow   R NWindowsBase, Version=4.0.0.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35W SPresentationCore, Version=4.0.0.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35\ XPresentationFramework, Version=4.0.0.0, Culture=neutral, PublicKeyToken=31bf3856ad364e35D 9http://schemas.microsoft.com/winfx/2006/xaml/presentation    R NSystem.Xaml, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089:x,http://schemas.microsoft.com/winfx/2006/xaml       � Title$  Admin Dashboard��$	��700��$	��900��.��� $��10q�.+��� -    ��Name$ MainTabControl��.@��� $��Overview��.���  � ColumnDefinitions �� $
��119* ��� $
��755* � � RowDefinitions �� $
��Auto ��� $��* ��� $��* �.+��� $��Admin Dashboard��$��24(�$
��Bold"�$
��Left=�$��
216,0,0,20q�$��1���� $��Users��$��1��$��0,0,0,10q�$��2��.��&   System.Windows.Controls.DataGrid  -   $��UsersDataGrid��   AutoGenerateColumns .     
IsReadOnly .    Columns 0  *System.Windows.Controls.DataGridTextColumn  ,  &System.Windows.Controls.DataGridColumn   Header$ Account Number��   Width5  /System.Windows.Controls.DataGridLengthConverter$ * 1  +System.Windows.Controls.DataGridBoundColumn	   Binding	 �� *AccountNumber+  $ 
First Name��$ * 	 �� *	FirstName+  $ 	Last Name��$ * 	 �� *
LastName+  $ Balance (€)��$ * 	 �� *	Balance+  $ Status��$ * 	 �� *Status+�� $��Recent Transactions��$��1��$��2��$��	0,292,0,0q�$��2��.��  -   $��TransactionsDataGrid�� .   .    $
 Type��$ * 	 �� *Type+  $ Account Number��$ * 	 �� *AccountNumber+  $ Amount (€)��$ * 	 �� *Amount+  $
 Time��$ * 	 �� *Time+�� -   
 ��Name$
 ManageAccountsTab��$��Manage Accounts��.���  �� $
��Auto ��� $��* �.+��� $��Add/Edit Account��$��0��$��0,0,0,10q�.��� $��10q� �� $
��Auto ��� $��* ��� $
��Auto ��� $��* � �� $
��Auto ��� $
��Auto ��� $
��Auto ��� $
��Auto ��� $
��Auto ��� $
��Auto �.+��� $��Account Number:��$��0��$��0��$��Center=�$��5q��� -   $!��AddEditAccountNumberTextBox��$��0��$��1��$��5q��� $��First Name:��$��1��$��0��$��Center=�$��5q��� -   $��AddEditFirstNameTextBox��$��1��$��1��$��5q��� $��
Last Name:��$��2��$��0��$��Center=�$��5q��� -   $��AddEditLastNameTextBox��$��2��$��1��$��5q��� $��Balance (€):��$��3��$��0��$��Center=�$��5q��� -   $��AddEditBalanceTextBox��$��3��$��1��$��5q��� $��Status:��$��4��$��0��$��Center=�$��5q��� -	   $��AddEditStatusComboBox��$��4��$��1��$��5q�.@��� $��active�� �� 
IsSelected . �� $��blocked���� $��	PIN Code:��$��5��$��0��$��Center=�$��5q�G� -
   $��AddEditPinCodePasswordBox��$��5��$��1��$��5q���  �� Orientation$ 
Horizontal=�$��5��$��3��$
��Left=�$��35,5,0,5q�.+��� -    ��Name$ AddAccountButton��$��Add Account��$	��100��$��5q��� -   $ EditAccountButton��$��Edit Account��$	��100��$��5q��� $��Accounts List��$��1��.���  �� $
��Auto ��� $��* �.+��� $ 
Horizontal=�$��0��$��5q�.+��� $ Vertical=�$��5q�.+��� $��Accountnummer:���� -   $ ��SearchAccountNumberTextBox��$	��150��$��5q��� $ Vertical=�$��5q�.+��� $��Achternaam:���� -   $��SearchLastNameTextBox��$	��150��$��5q��� -   $ SearchAccountsButton��$��Zoeken��$	��100��$��5q�$��27���� -   $ ClearSearchButton��$��Clear��$	��100��$��5q�$��27���� -   $ BlockAccountButton��$��Block Account��$	��120��$��5q�$��27���� -   $ DeleteAccountButton��$��Delete Account��$	��120��$��5q�$��27��  -   $��AccountsDataGrid��$��1�� .   . $��5q�   SelectionMode$ Single=�   $ Account Number��$ * 	 �� *AccountNumber+  $ 
First Name��$ * 	 �� *	FirstName+  $ 	Last Name��$ * 	 �� *
LastName+  $ Balance (€)��$ * 	 �� *	Balance+  $ Status��$ * 	 �� *Status+