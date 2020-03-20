
// Initialize the Amazon Cognito credentials provider
AWS.config.region = 'us-east-1'; // Region
AWS.config.credentials = new AWS.CognitoIdentityCredentials({
	 IdentityPoolId: 'us-east-1:e153e5f9-2dfe-44c0-a847-038a4f644647',
});
