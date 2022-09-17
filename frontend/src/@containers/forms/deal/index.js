import { connect } from 'react-redux';
import * as thunks from './thunks';
import * as actions from './actions';

const mapStateToProps = state => ({
    form: state.forms.deal,
    show: state.forms.deal.show,
});

const mapDispatcherToState = dispatch => ({
    noteText: "Editing a campaign's company will unassign all the agents from its integrations.",
    loadForm: deal => dispatch(actions.loadDeal(deal)),
    changeForm: field => dispatch(actions.changeDeal(field)),
    saveForm: deal => dispatch(thunks.saveDeal(deal)),
    saveFormDeal: deal => dispatch(thunks.saveDeal(deal)),
});

export default connect(mapStateToProps, mapDispatcherToState);