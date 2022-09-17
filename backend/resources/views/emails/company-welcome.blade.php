<style type="text/css">
	.emailbody {
		width:100%;
		height:100%;
		display:block;
		margin:0 auto;
		background:#f1f4f5;
		padding: 60px 0;
	}

	.bigcont {
		max-width: 380px;
		background:#fff;
		border: 1px dashed #b3b3b3;
		padding: 55px;
		margin: 0 auto;
		border-radius: 6px;
		color:#354771;
	}

	.emailfot {
		max-width: 450px;
		text-align: left;
		font-size: 12px;
		margin: 20px auto;
		color:#797979;

	}

	.toplogocont {
		text-align:center;
		display:block;
		width:100%;
	}

	.toplogocont img {
		width: 120px;
		margin: 10px auto !important;
		text-align: center;
		padding: 15px;
		display:block;
	}

	.bigcont p {
		font-size: 18px;
		font-family: Arial, sans-serif;
		font-weight: 400;
		line-height: 26px;
	}

	.bigcont h1 {
		font-size: 17px;
		font-family: Arial, sans-serif;
		font-weight: 600;
		color:#354771;
	}

	.bigcont h2 {
		font-size: 17px;
		font-family: Arial, sans-serif;
		font-weight: 600;
	}

	.bigcont a {

		display: block;
		background:#005ff8;
		color: #fff;
		font-size: 16px;
		font-weight: bold;
		font-family: Arial,sans-serif;
		margin: 30px auto;
		padding: 12px 30px;
		border-radius: 3px;
		text-decoration: none;
		text-align: center;
		white-space: nowrap;
		box-sizing: border-box;

	}

	.bigcont .smallnote {

		font-size:12px;
		text-align:center;
	}

	.bigcont .smallnote a {

		color:#005ff8;
		text-decoration:none ;
		background:transparent;
		font-size:12px;
		padding:unset !important;
		display:inline;
	}

</style>

<div class="emailbody" style="width:100%; height:100%; display:block; margin:0 auto; background:#f1f4f5; padding: 60px 0;">
	<div class="toplogocont" style="text-align:center; display:block; width:100%; "><img src="http://convertlead.com/wp-content/bloglogo.png" style="width: 120px; margin: 10px auto !important; text-align: center; padding: 15px; display:block;"></img></div>
	<div class="bigcont" style="max-width: 320px; background:#fff; border: 1px dashed #b3b3b3; padding: 70px 55px 55px 55px; margin: 0 auto; border-radius: 6px; color:#354771;">
		<p style="font-size: 18px; font-family: Arial, sans-serif; font-weight: 400; line-height: 26px; ">Hey {{$company->name}}, </br></br> You've just been assigned to a ConvertLead Agency account.</br></br>  Welcome!</p>


		<p style="font-size: 18px; font-family: Arial, sans-serif; font-weight: 400; line-height: 26px; ">You'll be able to login to your account using the following details:</p>
		<h1 style=" font-size: 17px; font-family: Arial, sans-serif; font-weight: 600; color:#354771; ">User: {{$company->email}}</h1>
		<h1 style=" font-size: 17px; font-family: Arial, sans-serif; font-weight: 600; color:#354771; ">Password: {{$password}}</h1>

		<a href="https://app.convertlead.com" style="display: block; background:#005ff8; color: #fff; font-size: 16px; font-weight: bold; font-family: Arial,sans-serif; margin: 30px auto; padding: 12px 30px; border-radius: 3px; text-decoration: none; text-align: center;
		white-space: nowrap; box-sizing: border-box; ">Go to login page</a>
		<p style="font-size:12px ; text-align:center ; ">Need Help? <a href="http://support.convertlead.com" style="color:#005ff8; text-decoration:none ;
		background:transparent ; font-size:12px; padding:unset !important; display:inline;">Let us know</a></p>

	</div>
	<div class="emailfot" style="max-width: 420px; text-align: left; font-size: 12px; margin: 20px auto; color:#797979;">
		This is an automated message. Please do not reply. You are receiving this email because you have an agent account on ConvertLead. If you wish to unsubscribe, please visit your ConvertLead settings.
	</div>

</div>

