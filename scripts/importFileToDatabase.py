import pandas as pd
import sqlalchemy as sa

tesco = pd.read_csv('../files/tesco.csv', parse_dates=['week_commencing'], dayfirst=True)
waitrose = pd.read_csv('../files/waitrose.csv', parse_dates=['week_commencing'], dayfirst=True)

# Reshape
tesco = tesco.set_index(
	[x for x in tesco.columns if x not in ('metric', 'value')]) \
	.pivot(columns = 'metric') \
	.reset_index()
tesco.columns = waitrose.columns

# Regex extract money value from £x.xx string
for df in (tesco, waitrose):
	df['sales_value'] = pd.to_numeric(
		df['sales_value'].str.extract(r'£([\d.]+)',expand=False)
		)

engine = sa.create_engine("mysql+pymysql://user:password@database:3306/retail")
print("DB Connected")
tesco.to_sql('example_table', index=False, con=engine, if_exists='append')
waitrose.to_sql('example_table', index=False, con=engine, if_exists='append')
print("Data pushed")