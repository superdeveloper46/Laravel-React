import {connect} from 'react-redux';
import * as thunks from './thunks';
import * as actions from './actions';

const mapStateToProps = state => ({
    isAuthorised: state.auth.session.isAuthorised,
    session: state.auth.session
});

const mapActionsToProps = dispatch => ({
    addDeviceToken: (deviceToken) => dispatch(actions.addDeviceToken(deviceToken)),
    registerDeviceToken: () => dispatch(thunks.registerDeviceToken()),
    init: () => dispatch(thunks.init()),
    login: (auth) => dispatch(thunks.login(auth)),
    logout: () => dispatch(thunks.logout()),
});

export default connect(mapStateToProps, mapActionsToProps);
