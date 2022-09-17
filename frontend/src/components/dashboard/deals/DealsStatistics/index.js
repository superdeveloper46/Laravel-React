import React from "react";
import { compose } from 'redux';
import { DateTime } from "luxon";
import * as R from 'ramda';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Tooltip,
  XAxis,
  YAxis
} from "recharts";
import { Progress } from 'semantic-ui-react'


import { DealsContainer } from "@containers";

import './index.scss'
import { CustomTooltip } from "./CustomTooltip";

class DealsStatistics extends React.PureComponent {
  state = {
    totalLeadsCount: 0,
    integrations: [{
      "totalLeadsCount": 0,
      "integration": "ZAPIER",
      "leadsPercentage": 0,
      "integrationDisplayName": "Zapier"
    },
    {
      "totalLeadsCount": 0,
      "integration": "FACEBOOK",
      "leadsPercentage": 0,
      "integrationDisplayName": "Facebook Lead Ads"
    },
    {
      "totalLeadsCount": 0,
      "integration": "INSTAPAGE",
      "leadsPercentage": 0,
      "integrationDisplayName": "Inastapage"
    },
    {
      "totalLeadsCount": 0,
      "integration": "WEBHOOK",
      "leadsPercentage": 0,
      "integrationDisplayName": "WebHook"
    },
    {
      "totalLeadsCount": 0,
      "integration": "CLICKFUNNELS",
      "leadsPercentage": 0,
      "integrationDisplayName": "ClickFunnels"
    }]
  }

  componentDidMount() {
    if (this.props.dealIds && this.props.dealIds.length) {
      this.props.fetchDealsStatistics(
        this.props.dealIds,
        null,
        DateTime.local().endOf('week').setZone('utc').toString()
      );
    }
  }

  onDisplay(payload) {
    const createdDate = R.path([0, 'payload', 'records', 0, 'created_date'], payload);
    if (createdDate) {
      this.props.dealDisplayGraphicDate(createdDate);
    } else {
      this.props.dealDisplayGraphicDate('all');
    }
  }

  onCloseSidebar() {
    this.props.onClose();
  }

  render() {
    const dealsCount = +(this.props.dealIds && this.props.dealIds.length);
    const data = this.props.dealsGraphic.filter(
      deal => new Date(deal.records.created_date).toISOString() > DateTime.local().startOf('week').toISODate().toString()
    );
    const chartIntegrations = this.props.dealsSelectedGraphicStatistics.length ? this.props.dealsSelectedGraphicStatistics : this.state.integrations;

    return (<div className="DealStatistics">
      <div className="btnClose" onClick={this.onCloseSidebar.bind(this)}><i className="flaticon stroke x-2"></i></div>
      <div className="statswrapper">
        <div className="deal-header">
          <div>{dealsCount} Campaign/s Selected</div>
          <h2>Weekly Leads</h2>
        </div>
        <div onMouseLeave={() => this.props.dealDisplayGraphicDate('all')} className="deals-chart">
          {data.length ?
            <BarChart width={310} height={180} data={data}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="name" tick={{ fontSize: 10 }} interval={0} />
              <YAxis onMouseOver={(...args) => console.log('hover', args)} />
              <Tooltip content={<CustomTooltip onDisplay={this.onDisplay.bind(this)} />} />
              <Bar dataKey="totalLeadsCount" fill="#4d77ff" barSize={10} />
            </BarChart>
            :
            <>
			<p className="p-25">
              <img src="img/nodata2.svg" />
			  </p>
            </>
          }
        </div>
        <div className="deals-chart-description">
          <h3>Leads by Source</h3>
          <div className="deals-chart-source">
            {
              chartIntegrations && chartIntegrations.map(record => (
                <div className="deal-source" key={`integration-${record.integration.toLowerCase()}`}>
                  <div className="data-source-header">
                    <label className="deal-source-label">{record.integrationDisplayName}</label>
                    <div className="deal-source-percentage">{record.leadsPercentage} %</div>
                  </div>
                  <Progress percent={record.leadsPercentage} className={`integration-bar-${record.integration.toLowerCase()}`} />
                </div>
              ))
            }
          </div>
        </div>
      </div></div>)
  }
}

DealsStatistics.defaultProps = {
  dealIds: [],
};

export default compose(DealsContainer)(DealsStatistics);
