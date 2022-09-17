import React, { Component } from 'react';
import { Link } from 'react-router-dom';
import { compose } from 'recompose';
import * as moment from 'moment';
import {
  Table,
  Segment,
  Pagination,
  Button,
  Form,
  Icon,
  Select,
  Popup, Confirm, Label,
} from 'semantic-ui-react';
import './index.scss';
import { BreadCrumbContainer, DealsContainer, CompaniesContainer, LeadsContainer, LeadFormContainer } from '@containers';
import Loader from '../loader';
import * as R from "ramda";
import { getSelectBoxStatuses } from "@models/lead-statuses";
import { Auth } from "@services";
import DatePickerSelect from "../@common/datepicker";
import { AvatarImage } from '../@common/image';
import { DATE_FORMAT } from '@constants';
import ButtonGroup from '../@common/button-group';

const defaultStatus = { key: '', text: 'All statuses', value: '' };
const companies = [
  { key: '', text: 'All companies', value: '' },
];
const sorByFiled = [
  {
    key: 'name.desc',
    value: 'name.desc',
    text: 'Name Descending',
  },
  {
    key: 'name.asc',
    value: 'name.asc',
    text: 'Name Ascending',
  },
  {
    key: 'company.desc',
    value: 'company.desc',
    text: 'Company Descending',
  },
  {
    key: 'company.asc',
    value: 'company.asc',
    text: 'Company Ascending',
  },
  {
    key: 'source.desc',
    value: 'source.desc',
    text: 'Source Descending',
  },
  {
    key: 'source.asc',
    value: 'source.asc',
    text: 'Source Ascending',
  },
  {
    key: 'created_at.desc',
    value: 'created_at.desc',
    text: 'Date Descending',
  },
  {
    key: 'created_at.asc',
    value: 'created_at.asc',
    text: 'Date Ascending',
  }
]
class Leads extends React.Component {
  dateDisplayFormat = 'MM/DD/Y';

  state = {
    open: false,
    active: false,
    status: null,
    leadId: null,
    companyId: null,
    campaignId: null,
    startDateDisplay: moment('2000-01-01').format(this.dateDisplayFormat),
    endDateDisplay: moment().endOf('isoWeek').format(this.dateDisplayFormat),
    startDate: moment('2000-01-01').format('Y-MM-DD'),
    endDate: moment().endOf('isoWeek').format('Y-MM-DD'),
  };

  getSort = field => {
    const fieldStatus = R.path(['query', 'sort', field], this.props);
    if (fieldStatus === true) {
      return 'sort amount down';
    }
    if (fieldStatus === false) {
      return 'sort amount up';
    }
    return 'sort';
  };

  onSearch = (event, data) => {
    this.props.searchLeads(data.value);
  };

  gotoPage = (event, data) => {
    this.props.gotoPage(data.activePage);
  };

  openConfirmModal = (open = true, companyId, leadId = null) => {
    this.setState({ open, companyId, leadId });
  };

  onConfirm = () => {
    this.setState({ open: false });
    this.props.delete(this.state.companyId, this.state.leadId);
  };

  filterByCompany = (event, data) => {
    this.props.filterLeads({
      companyId: data.value,
    });
  };

  filterByStatus = (event, data) => {
    this.setState({
      status: data.value,
    });
    this.props.filterLeads({
      statusType: data.value,
    });
  };

  onChangeDateFrom = (date) => {
    this.setState({
      startDate: moment(date).format('Y-MM-DD'),
      startDateDisplay: moment(date).format(this.dateDisplayFormat),
    });
  };

  onChangeDateTo = (date) => {
    this.setState({
      endDate: moment(date).format('Y-MM-DD'),
      endDateDisplay: moment(date).format(this.dateDisplayFormat),
    });

    this.props.filterLeads({
      startDate: this.state.startDate,
      endDate: moment(date).format('Y-MM-DD'),
    });
  };

  onRestDate = () => {
    this.setState({
      startDateDisplay: moment().startOf('isoWeek').format(this.dateDisplayFormat),
      endDateDisplay: moment().endOf('isoWeek').format(this.dateDisplayFormat),
      startDate: moment().startOf('isoWeek').format('Y-MM-DD'),
      endDate: moment().endOf('isoWeek').format('Y-MM-DD'),
    });

    this.props.filterLeads({
      startDate: moment().startOf('isoWeek').format('Y-MM-DD'),
      endDate: moment().endOf('isoWeek').format('Y-MM-DD'),
    });
  };

  componentWillMount() {
    const companyId = +R.pathOr('', ['match', 'params', 'companyId'], this.props);
    const campaignId = +R.pathOr('', ['match', 'params', 'campaignId'], this.props);
    const agentId = +R.pathOr('', ['match', 'params', 'agentId'], this.props);
    const sort_by = +R.pathOr('', ['match', 'params', 'sort_by'], this.props);
    this.setState({
      companyId,
      campaignId,
    });

    // this.props.filterLeads({
    //   companyId,
    //   campaignId,
    //   agentId,
    //   startDate: this.state.startDate,
    //   endDate: this.state.endDate,
    //   sort_by
    // });
    

    this.props.addBreadCrumb({
      name: 'Leads',
      path: '/leads'
    });

    if (Auth.isAgency) {
      this.props.loadSelectBoxCompanies();
    }

    this.props.filterDealsByDealId(null);
    this.props.filterDealCampaignsById(null);
  }

  exportTo = (type) => {
    this.props.exportTo({
      type,
      statusType: this.props.query.filters.statusType,
      search: this.props.query.search,
      showDeleted: this.props.query.showDeleted,
      companyId: this.props.query.filters.companyId,
      campaignId: this.props.query.filters.campaignId,
      startDate: this.props.query.filters.startDate,
      endDate: this.props.query.filters.endDate,
    });
  };

  onLeadEnterDisplayNotes = (lead) => {
    this.props.onPreviewLeadChange(lead)
    this.setState({
      companyId: lead.company_id
    });
  }

  sortBy = (event, data) => {
    this.props.filterLeads({
      sort_by: data.value,
    });
  };

  render() {
    let leads = this.props.leads || [];
    leads = leads.sort(function(a, b){
      return b.smsReplayCount - a.smsReplayCount
    }); 

    const { pagination, statuses, query } = this.props;
    const {
      companyId,
      campaignId,
      startDateDisplay,
      endDateDisplay,
      startDate,
      endDate,
    } = this.state;

    return (
      <div>
        <Confirm open={this.state.open} onCancel={this.openConfirmModal.bind(this, false)} onConfirm={this.onConfirm} />
        <Segment basic>
          <div className="leadFilters">
            <div className="field">
              <Form>
                <Form.Group widths='equal' className='filter white'>
                  {
                    !campaignId && Auth.isAgency ?
                      <Form.Field
                        control={Select}
                        options={[...companies, ...this.props.selectBoxCompanies]}
                        placeholder='All companies'
                        search
                        onChange={this.filterByCompany}
                        defaultValue={companyId || null}
                        searchInput={{ id: 'form-companies-list' }} />
                      : null
                  }

                  <Form.Field
                    control={Select}
                    options={[defaultStatus, ...getSelectBoxStatuses]}
                    placeholder='All statuses'
                    search
                    onChange={this.filterByStatus}
                    searchInput={{ id: 'form-statuses-list' }}
                  />
                </Form.Group>
                <Popup position='bottom left'
                  trigger={
                    <Form.Field>
                      <Button>
                        <Icon name='calendar alternate outline' />
                        {startDateDisplay} - {endDateDisplay}
                      </Button>
                    </Form.Field>} flowing hoverable>

                  <DatePickerSelect onChangeDateFrom={this.onChangeDateFrom}
                    onChangeDateTo={this.onChangeDateTo}
                    onRestDate={this.onRestDate}
                    from={new Date(startDate)} to={new Date(endDate)} />
                </Popup>
                <Form.Field
                  control={Select}
                  className="sortby"
                  options={sorByFiled}
                  label={{ children: 'Sort by', htmlFor: 'leads-sort-by' }}
                  placeholder='Sort by '
                  search
                  onChange={this.sortBy}
                  defaultValue={this.props.filters.sortBy}
                  onSearchChange={this.sortBy}
                  searchInput={{ id: 'campaign-sort-by' }}
                />
              </Form>

            </div>
            <div className='exportbox'>Export your data
              <a href='#export-csv' onClick={this.exportTo.bind(this, 'TYPE_LEADS_CSV')}>.csv export</a>
              <a href='#export-pdf' onClick={this.exportTo.bind(this, 'TYPE_LEADS_PDF')}>.pdf export</a>
            </div>
          </div>
          <Loader />
          <Table singleLine>
            <Table.Header>
              <Table.Row>
                <Table.HeaderCell><span className='table-head blue'>Status</span></Table.HeaderCell>
                <Table.HeaderCell><span className='table-head blue'>Name
                  <Icon name={this.getSort('name')}
                    onClick={this.props.sort.bind(this, 'name')} /></span>
                </Table.HeaderCell>
                <Table.HeaderCell><span className='table-head blue'>Assigned to</span></Table.HeaderCell>

                <Table.HeaderCell><span className='table-head blue'>Phone Number</span></Table.HeaderCell>
                {
                  Auth.isAgency
                    ? <Table.HeaderCell><span className='table-head blue'>Company
                      <Icon name={this.getSort('company')}
                        onClick={this.props.sort.bind(this, 'company')} /></span>
                    </Table.HeaderCell>
                    : null
                }
                <Table.HeaderCell><span className='table-head blue'>Source
                  <Icon name={this.getSort('campaign')}
                    onClick={this.props.sort.bind(this, 'campaign')} /></span>
                </Table.HeaderCell>
                <Table.HeaderCell><span className='table-head blue'>Actions</span></Table.HeaderCell>
              </Table.Row>
            </Table.Header>
            <Table.Body>
              {
                leads.map((lead, index) => (

                  <Table.Row onClick={() => this.onLeadEnterDisplayNotes(lead)} key={index}>
                    <Table.Cell>
                      <div className={`lead-status-icon lead-status-${lead.status[0].toLowerCase()}`}>
                        {(lead.fullname && lead.fullname[0]) || statuses[lead.status].icon}
                        {
                          lead.smsReplayCount && (
                            <Label color='red' floating>
                              {lead.smsReplayCount}
                            </Label>
                          ) || ('')
                        }
                      </div>
                    </Table.Cell>
                    <Table.Cell>
                      {lead.fullname}
                      <div className='date-added'>
                        added {moment.utc(lead.created_at).local().format(`${DATE_FORMAT} H:mm`)}
                      </div>
                    </Table.Cell>
                    <Table.Cell>
                      {
                        lead.agent && <Link
                          to={`/agents/${lead.agent.id}/profile`}>{lead.agent.name}</Link>
                      }
                      {/* {
                        lead.agent && <Link
                          to={`/agents`}>{lead.agent.name}</Link>
                      }                       */}
                    </Table.Cell>

                    <Table.Cell>{lead.phone}</Table.Cell>
                    {
                      Auth.isAgency
                        ? <Table.Cell>
                          {
                            lead.company
                              ? <div className="comp-logo-box">
                                <AvatarImage avatar src={lead.company.avatar_path} rounded
                                  size='mini' />
                                <Link to={`/companies/${lead.company.id}/profile`}>
                                  {lead.company.name}
                                </Link>
                              </div>
                              : null
                          }
                        </Table.Cell>
                        : null
                    }
                    <Table.Cell><Link to={{
                      pathname: (
                        Auth.isAgency
                          ? `/companies/${lead.company.id}/deals/${lead.deal_id}/campaigns`
                          : `/deals/${lead.deal_id}/campaigns`
                      ),
                      state: { deal: lead.campaign.deal }
                    }}>{lead.campaign.name}</Link></Table.Cell>

                    <Table.Cell>
                      <Link to={`/companies/${lead.company_id}/leads/${lead.id}/notes`}>
                        <i class="ti ti-user"></i>
                      </Link>
                      {
                        !lead.deleted_at
                          ? <ButtonGroup>
                            <Button onClick={this.props.loadForm.bind(this, {
                              ...lead,
                              company_id: lead.company.id,
                              show: true
                            })}>Edit</Button>
                            <Button
                              onClick={this.openConfirmModal.bind(this, true, lead.company_id, lead.id)}>Archive</Button>
                          </ButtonGroup>
                          : null
                      }
                    </Table.Cell>
                  </Table.Row>
                ))
              }

            </Table.Body>
          </Table>
        </Segment>
        <Segment textAlign='right' attached='bottom'>
          <Pagination onPageChange={this.gotoPage}
            defaultActivePage={pagination.current_page}
            prevItem={null}
            nextItem={null}
            totalPages={pagination.last_page} />
        </Segment>
      </div>
    )
  }
}

export default compose(BreadCrumbContainer, DealsContainer, CompaniesContainer, LeadsContainer, LeadFormContainer)(Leads);
